<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Notifications\Notifiable;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Exceptions\Http\Server\ServerStateConflictException;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;
use UnexpectedValueException;

/**
 * @property int|null $database_limit Larastan's schema dump parser misses that this column is nullable
 * @property-read Node $node
 * @property-read User $user
 */
#[Attributes\Identifiable('serv')]
#[Guarded(['id', self::CREATED_AT, self::UPDATED_AT, 'deleted_at', 'installed_at'])]
class Server extends Model implements Identifiable
{
    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    use HasRealtimeIdentifier;
    use Notifiable;

    public const string RESOURCE_NAME = 'server';

    public const string STATUS_INSTALLING = 'installing';

    public const string STATUS_INSTALL_FAILED = 'install_failed';

    public const string STATUS_REINSTALL_FAILED = 'reinstall_failed';

    public const string STATUS_SUSPENDED = 'suspended';

    public const string STATUS_RESTORING_BACKUP = 'restoring_backup';

    private const string ADMIN_ROUTE_BINDING_FIELD = 'admin_identifier';

    /**
     * New servers have the OOM killer disabled unless the request says otherwise.
     */
    protected $attributes = [
        'status' => self::STATUS_INSTALLING,
        'oom_disabled' => true,
        'installed_at' => null,
    ];

    protected $with = ['allocation'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'node_id' => 'integer',
            'skip_scripts' => 'boolean',
            'owner_id' => 'integer',
            'memory' => 'integer',
            'swap' => 'integer',
            'disk' => 'integer',
            'io' => 'integer',
            'cpu' => 'integer',
            'oom_disabled' => 'boolean',
            'allocation_id' => 'integer',
            'egg_id' => 'integer',
            'database_limit' => 'integer',
            'allocation_limit' => 'integer',
            'backup_limit' => 'integer',
            'deleted_at' => 'datetime',
            'installed_at' => 'datetime',
        ];
    }

    /**
     * Returns the format for server allocations when communicating with the Daemon.
     *
     * @return array<string, list<int>> ports keyed by the allocation IP
     */
    public function getAllocationMappings(): array
    {
        $mappings = [];
        foreach ($this->allocations as $allocation) {
            if ($allocation->node_id === $this->node_id) {
                $mappings[$allocation->ip] ??= [];
                $mappings[$allocation->ip][] = $allocation->port;
            }
        }

        return $mappings;
    }

    public function isInstalled(): bool
    {
        return $this->status !== self::STATUS_INSTALLING && $this->status !== self::STATUS_INSTALL_FAILED;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Whether a reinstall would run the egg's install script. A server that skips its install
     * script can still be reinstalled while its install is unfinished or has failed.
     */
    public function canBeReinstalled(): bool
    {
        return ! $this->skip_scripts || in_array($this->status, [
            self::STATUS_INSTALLING,
            self::STATUS_INSTALL_FAILED,
            self::STATUS_REINSTALL_FAILED,
        ], true);
    }

    public function resolveRouteBinding($value, $field = null): ?\Illuminate\Database\Eloquent\Model
    {
        if ($field !== self::ADMIN_ROUTE_BINDING_FIELD) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (! is_int($value) && ! is_string($value)) {
            return null;
        }

        $routeValue = (string) $value;
        if (ctype_digit($routeValue)) {
            $server = self::query()->whereKey($routeValue)->first();
            if (($server) !== null) {
                return $server;
            }
        }

        return self::query()->whereUuidOrShort($routeValue)->first();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Subuser, $this>
     */
    public function subusers(): HasMany
    {
        return $this->hasMany(Subuser::class, 'server_id', 'id');
    }

    /**
     * Gets the default allocation for a server.
     *
     * @return HasOne<Allocation, $this>
     */
    public function allocation(): HasOne
    {
        return $this->hasOne(Allocation::class, 'id', 'allocation_id')->chaperone('server');
    }

    /**
     * @return HasMany<Allocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class, 'server_id')->chaperone('server');
    }

    /**
     * @return HasOne<Egg, $this>
     */
    public function egg(): HasOne
    {
        return $this->hasOne(Egg::class, 'id', 'egg_id');
    }

    /**
     * The tag slugs that describe this server's game, taken from its egg. These are
     * what feature gating matches on, replacing the nest a server used to carry.
     *
     * @return string[]
     */
    public function tagSlugs(): array
    {
        $this->loadMissing('egg.tags');

        return $this->egg === null ? [] : $this->egg->tags->map(fn (Tag $tag): string => $tag->slug)->all();
    }

    public function hasTag(string $slug): bool
    {
        return in_array($slug, $this->tagSlugs(), true);
    }

    /**
     * @return HasMany<EggVariable, $this>
     */
    public function variables(): HasMany
    {
        // WARNING: lazy/instance use only. The join below captures $this->id, and an
        // eager load (with()/load()/loadMissing()) rebuilds the relation on a blank
        // model, degrading the join to "server_id is null" and nulling server_value.
        return $this->hasMany(EggVariable::class, 'egg_id', 'egg_id')
            ->select(['egg_variables.*', 'server_variables.variable_value as server_value'])
            ->leftJoin('server_variables', function (JoinClause $join): void {
                // Don't forget to join against the server ID as well since the way we're using this relationship
                // would actually return all the variables and their values for _all_ servers using that egg,
                // rather than only the server for this model.
                //
                // @see https://github.com/pterodactyl/panel/issues/2250
                $join->on('server_variables.variable_id', 'egg_variables.id')
                    ->where('server_variables.server_id', $this->id);
            });
    }

    /**
     * Gets the environment variable overrides stored specifically for this server.
     *
     * @return HasMany<ServerVariable, $this>
     */
    public function serverVariables(): HasMany
    {
        return $this->hasMany(ServerVariable::class);
    }

    /**
     * The egg's variables with this server's values as server_value. When a list has eager
     * loaded egg.variables and serverVariables this runs no query for each server.
     *
     * @return Collection<int, EggVariable>
     */
    public function resolvedVariables(): Collection
    {
        if (! $this->relationLoaded('egg')
            || ! $this->egg?->relationLoaded('variables')
            || ! $this->relationLoaded('serverVariables')
        ) {
            return $this->variables()->get();
        }

        $overrides = $this->serverVariables->pluck('variable_value', 'variable_id');

        return $this->egg->variables->map(function (EggVariable $variable) use ($overrides): EggVariable {
            $resolved = clone $variable;
            $resolved->setAttribute('server_value', $overrides->get($variable->id));

            return $resolved;
        });
    }

    /**
     * The startup command with its variables filled in. A variable the user cannot view, or
     * every variable when $hideAllValues is set, shows as "[hidden]".
     */
    public function startupCommand(bool $hideAllValues = false): string
    {
        $this->loadMissing('allocation');
        $allocation = $this->allocation ?? throw new UnexpectedValueException('The server does not have a primary allocation.');

        $find = ['{{SERVER_MEMORY}}', '{{SERVER_IP}}', '{{SERVER_PORT}}'];
        $replace = [(string) $this->memory, $allocation->ip, (string) $allocation->port];

        foreach ($this->resolvedVariables() as $variable) {
            $find[] = '{{'.$variable->env_variable.'}}';
            $replace[] = (string) (($variable->user_viewable && ! $hideAllValues) ? ($variable->server_value ?? $variable->default_value) : '[hidden]');
        }

        return str_replace($find, $replace, $this->startup);
    }

    /**
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * @return HasMany<Schedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * @return HasMany<Database, $this>
     */
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class)->chaperone('server')->orderBy('id');
    }

    /**
     * The server's location is whatever its node belongs to, so this resolves
     * through the node with local keys - the same native emulation of an inverse
     * through-relation used elsewhere in this codebase.
     *
     * @return HasOneThrough<Location, Node, $this>
     */
    public function location(): HasOneThrough
    {
        return $this->hasOneThrough(Location::class, Node::class, 'id', 'id', 'node_id', 'location_id');
    }

    /**
     * @return HasOne<ServerTransfer, $this>
     */
    public function transfer(): HasOne
    {
        return $this->hasOne(ServerTransfer::class)->whereNull('successful')->orderByDesc('id');
    }

    /**
     * @return HasMany<Backup, $this>
     */
    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    /**
     * @return HasManyThrough<Mount, MountServer, $this>
     */
    public function mounts(): HasManyThrough
    {
        return $this->hasManyThrough(Mount::class, MountServer::class, 'server_id', 'id', 'id', 'mount_id');
    }

    /**
     * Returns all of the activity log entries where the server is the subject.
     *
     * @return MorphToMany<ActivityLog, $this>
     */
    public function activity(): MorphToMany
    {
        return $this->morphToMany(ActivityLog::class, 'subject', 'activity_log_subjects');
    }

    /**
     * The server's activity without disabled events. With activity.hide_admin_activity set,
     * entries by root admins who are neither the owner nor a subuser are left out.
     *
     * @return MorphToMany<ActivityLog, $this>
     */
    public function visibleActivity(): MorphToMany
    {
        $query = $this->activity()->select('activity_logs.*')->withoutDisabledEvents();

        if (config('activity.hide_admin_activity')) {
            $subusers = $this->subusers()->pluck('user_id')->merge([$this->owner_id]);

            $query->leftJoin('users', function (JoinClause $join): void {
                $join->on('users.id', 'activity_logs.actor_id')
                    ->where('activity_logs.actor_type', (new User)->getMorphClass());
            })->where(function (Builder $builder) use ($subusers): void {
                $builder->whereNull('users.id')
                    ->orWhere('users.root_admin', 0)
                    ->orWhereIn('users.id', $subusers);
            });
        }

        return $query;
    }

    /**
     * Checks if the server is currently in a user-accessible state. If not, an
     * exception is raised. This should be called whenever something needs to make
     * sure the server is not in a weird state that should block user access.
     *
     * @throws ServerStateConflictException
     */
    public function validateCurrentState(): void
    {
        throw_if($this->isSuspended()
        || $this->node->isUnderMaintenance()
        || ! $this->isInstalled()
        || $this->status === self::STATUS_RESTORING_BACKUP
        || ($this->transfer) !== null, ServerStateConflictException::class, $this);
    }

    /**
     * Checks if the server is currently in a transferable state. If not, an
     * exception is raised. This should be called whenever something needs to make
     * sure the server is able to be transferred and is not currently being transferred
     * or installed.
     */
    public function validateTransferState(): void
    {
        throw_if(! $this->isInstalled()
        || $this->status === self::STATUS_RESTORING_BACKUP
        || ($this->transfer) !== null, ServerStateConflictException::class, $this);
    }

    /**
     * @return Attribute<int, never>
     */
    protected function adminIdentifier(): Attribute
    {
        return Attribute::make(get: fn (): int => $this->id);
    }

    /**
     * Match a server by its full uuid or its eight-character short uuid.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function whereUuidOrShort(Builder $query, string $uuid): void
    {
        $query->where(fn (Builder $inner) => $inner->where('uuidShort', $uuid)->orWhere('uuid', $uuid));
    }
}
