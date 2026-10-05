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
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Exceptions\Http\Server\ServerStateConflictException;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;
use Pterodactyl\Support\JsonValueGuard;

/**
 * \Pterodactyl\Models\Server.
 *
 * @property int $id
 * @property string|null $external_id
 * @property string $uuid
 * @property string $uuidShort
 * @property int $node_id
 * @property string $name
 * @property string $description
 * @property string|null $status
 * @property bool $skip_scripts
 * @property int $owner_id
 * @property int $memory
 * @property int $swap
 * @property int $disk
 * @property int $io
 * @property int $cpu
 * @property string|null $threads
 * @property bool $oom_disabled
 * @property int $allocation_id
 * @property int $egg_id
 * @property string $startup
 * @property string $image
 * @property int|null $allocation_limit
 * @property int|null $database_limit
 * @property int $backup_limit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $installed_at
 * @property Collection|ActivityLog[] $activity
 * @property int|null $activity_count
 * @property Allocation|null $allocation
 * @property Collection|Allocation[] $allocations
 * @property int|null $allocations_count
 * @property Collection|Backup[] $backups
 * @property int|null $backups_count
 * @property Collection|Database[] $databases
 * @property int|null $databases_count
 * @property Egg|null $egg
 * @property Collection|Mount[] $mounts
 * @property int|null $mounts_count
 * @property Node $node
 * @property DatabaseNotificationCollection<int, DatabaseNotification>|DatabaseNotification[] $notifications
 * @property int|null $notifications_count
 * @property Collection|Schedule[] $schedules
 * @property int|null $schedules_count
 * @property Collection|Subuser[] $subusers
 * @property int|null $subusers_count
 * @property ServerTransfer|null $transfer
 * @property User $user
 * @property Collection|EggVariable[] $variables
 * @property int|null $variables_count
 * @property Collection|ServerVariable[] $serverVariables
 *
 * @method static ServerFactory factory(...$parameters)
 * @method static Builder|Server newModelQuery()
 * @method static Builder|Server newQuery()
 * @method static Builder|Server query()
 * @method static Builder|Server whereAllocationId($value)
 * @method static Builder|Server whereAllocationLimit($value)
 * @method static Builder|Server whereBackupLimit($value)
 * @method static Builder|Server whereCpu($value)
 * @method static Builder|Server whereCreatedAt($value)
 * @method static Builder|Server whereDatabaseLimit($value)
 * @method static Builder|Server whereDescription($value)
 * @method static Builder|Server whereDisk($value)
 * @method static Builder|Server whereEggId($value)
 * @method static Builder|Server whereExternalId($value)
 * @method static Builder|Server whereId($value)
 * @method static Builder|Server whereImage($value)
 * @method static Builder|Server whereIo($value)
 * @method static Builder|Server whereMemory($value)
 * @method static Builder|Server whereName($value)
 * @method static Builder|Server whereNodeId($value)
 * @method static Builder|Server whereOomDisabled($value)
 * @method static Builder|Server whereOwnerId($value)
 * @method static Builder|Server whereSkipScripts($value)
 * @method static Builder|Server whereStartup($value)
 * @method static Builder|Server whereStatus($value)
 * @method static Builder|Server whereSwap($value)
 * @method static Builder|Server whereThreads($value)
 * @method static Builder|Server whereUpdatedAt($value)
 * @method static Builder|Server whereUuid($value)
 * @method static Builder|Server whereUuidShort($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
#[Attributes\Identifiable('serv')]
#[Guarded(['id', self::CREATED_AT, self::UPDATED_AT, 'deleted_at', 'installed_at'])]
class Server extends Model implements Identifiable
{
    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    use HasRealtimeIdentifier;
    use Notifiable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'server';

    public const string STATUS_INSTALLING = 'installing';

    public const string STATUS_INSTALL_FAILED = 'install_failed';

    public const string STATUS_REINSTALL_FAILED = 'reinstall_failed';

    public const string STATUS_SUSPENDED = 'suspended';

    public const string STATUS_RESTORING_BACKUP = 'restoring_backup';

    private const string ADMIN_ROUTE_BINDING_FIELD = 'admin_identifier';

    /**
     * Default values when creating the model. We want to switch to disabling OOM killer
     * on server instances unless the user specifies otherwise in the request.
     */
    protected $attributes = [
        'status' => self::STATUS_INSTALLING,
        'oom_disabled' => true,
        'installed_at' => null,
    ];

    /**
     * The default relationships to load for all server models.
     */
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
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
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

        // SAFETY: the type check above limits route binding values to integers and strings.
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
     * Gets the user who owns the server.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Gets the subusers associated with a server.
     *
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
     * Gets all allocations associated with this server.
     *
     * @return HasMany<Allocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class, 'server_id')->chaperone('server');
    }

    /**
     * Gets information for the egg associated with this server.
     *
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

        return $this->egg === null ? [] : JsonValueGuard::stringList($this->egg->tags->pluck('slug')->all());
    }

    /**
     * Whether this server's egg carries the given tag slug.
     */
    public function hasTag(string $slug): bool
    {
        return in_array($slug, $this->tagSlugs(), true);
    }

    /**
     * Gets information for the service variables associated with this server.
     *
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
     * Gets information for the node associated with this server.
     *
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Gets information for the tasks associated with this server.
     *
     * @return HasMany<Schedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Gets all databases associated with a server.
     *
     * @return HasMany<Database, $this>
     */
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class)->chaperone('server');
    }

    /**
     * Returns the location that a server belongs to.
     *
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
     * Returns the associated server transfer.
     *
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
     * Returns all mounts that have this server has mounted.
     *
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
