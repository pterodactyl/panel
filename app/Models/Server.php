<?php

namespace Pterodactyl\Models;

use Closure;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Query\JoinClause;
use Znck\Eloquent\Traits\BelongsToThrough;
use Pterodactyl\Exceptions\Http\Server\ServerStateConflictException;

/**
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
 * @property string $threads
 * @property bool $oom_disabled
 * @property int $allocation_id
 * @property int $nest_id
 * @property int $egg_id
 * @property string $startup
 * @property string $image
 * @property int $allocation_limit
 * @property int $database_limit
 * @property int $backup_limit
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\User $user
 * @property \Pterodactyl\Models\Subuser[]|\Illuminate\Database\Eloquent\Collection $subusers
 * @property \Pterodactyl\Models\Allocation $allocation
 * @property \Pterodactyl\Models\Allocation[]|\Illuminate\Database\Eloquent\Collection $allocations
 * @property \Pterodactyl\Models\Node $node
 * @property \Pterodactyl\Models\Nest $nest
 * @property \Pterodactyl\Models\Egg $egg
 * @property \Pterodactyl\Models\EggVariable[]|\Illuminate\Database\Eloquent\Collection $variables
 * @property \Pterodactyl\Models\Schedule[]|\Illuminate\Database\Eloquent\Collection $schedule
 * @property \Pterodactyl\Models\Database[]|\Illuminate\Database\Eloquent\Collection $databases
 * @property \Pterodactyl\Models\Location $location
 * @property \Pterodactyl\Models\ServerTransfer $transfer
 * @property \Pterodactyl\Models\Backup[]|\Illuminate\Database\Eloquent\Collection $backups
 * @property \Pterodactyl\Models\Mount[]|\Illuminate\Database\Eloquent\Collection $mounts
 * @property \Pterodactyl\Models\AuditLog[] $audits
 */
class Server extends Model
{
    use BelongsToThrough;
    use Notifiable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'server';

    public const STATUS_INSTALLING = 'installing';
    public const STATUS_INSTALL_FAILED = 'install_failed';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_RESTORING_BACKUP = 'restoring_backup';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'servers';

    /**
     * Default values when creating the model. We want to switch to disabling OOM killer
     * on server instances unless the user specifies otherwise in the request.
     *
     * @var array
     */
    protected $attributes = [
        'status' => self::STATUS_INSTALLING,
        'oom_disabled' => true,
    ];

    /**
     * The default relationships to load for all server models.
     *
     * @var string[]
     */
    protected $with = ['allocation'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [self::CREATED_AT, self::UPDATED_AT, 'deleted_at'];

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', self::CREATED_AT, self::UPDATED_AT, 'deleted_at'];

    /**
     * @var array
     */
    public static $validationRules = [
        'external_id' => 'sometimes|nullable|string|between:1,191|unique:servers',
        'owner_id' => 'required|integer|exists:users,id',
        'name' => 'required|string|min:1|max:191',
        'node_id' => 'required|exists:nodes,id',
        'description' => 'string',
        'status' => 'nullable|string',
        'memory' => 'required|numeric|min:0',
        'swap' => 'required|numeric|min:-1',
        'io' => 'required|numeric|between:10,1000',
        'cpu' => 'required|numeric|min:0',
        'threads' => 'nullable|regex:/^[0-9-,]+$/',
        'oom_disabled' => 'sometimes|boolean',
        'disk' => 'required|numeric|min:0',
        'allocation_id' => 'required|bail|unique:servers|exists:allocations,id',
        'nest_id' => 'required|exists:nests,id',
        'egg_id' => 'required|exists:eggs,id',
        'startup' => 'required|string',
        'skip_scripts' => 'sometimes|boolean',
        'image' => 'required|string|max:191',
        'database_limit' => 'present|nullable|integer|min:0',
        'allocation_limit' => 'sometimes|nullable|integer|min:0',
        'backup_limit' => 'present|nullable|integer|min:0',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
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
        'nest_id' => 'integer',
        'egg_id' => 'integer',
        'database_limit' => 'integer',
        'allocation_limit' => 'integer',
        'backup_limit' => 'integer',
    ];

    /**
     * Returns the format for server allocations when communicating with the Daemon.
     */
    public function getAllocationMappings(): array
    {
        return $this->allocations->where('node_id', $this->node_id)->groupBy('ip')->map(function ($item) {
            return $item->pluck('port');
        })->toArray();
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
     * Gets the user who owns the server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Gets the subusers associated with a server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subusers()
    {
        return $this->hasMany(Subuser::class, 'server_id', 'id');
    }

    /**
     * Gets the default allocation for a server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function allocation()
    {
        return $this->hasOne(Allocation::class, 'id', 'allocation_id');
    }

    /**
     * Gets all allocations associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allocations()
    {
        return $this->hasMany(Allocation::class, 'server_id');
    }

    /**
     * Gets information for the nest associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nest()
    {
        return $this->belongsTo(Nest::class);
    }

    /**
     * Gets information for the egg associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function egg()
    {
        return $this->hasOne(Egg::class, 'id', 'egg_id');
    }

    /**
     * Gets information for the service variables associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variables()
    {
        return $this->hasMany(EggVariable::class, 'egg_id', 'egg_id')
            ->select(['egg_variables.*', 'server_variables.variable_value as server_value'])
            ->leftJoin('server_variables', function (JoinClause $join) {
                // Don't forget to join against the server ID as well since the way we're using this relationship
                // would actually return all of the variables and their values for _all_ servers using that egg,\
                // rather than only the server for this model.
                //
                // @see https://github.com/pterodactyl/panel/issues/2250
                $join->on('server_variables.variable_id', 'egg_variables.id')
                    ->where('server_variables.server_id', $this->id);
            });
    }

    /**
     * Gets information for the node associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Gets information for the tasks associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedule()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Gets all databases associated with a server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function databases()
    {
        return $this->hasMany(Database::class);
    }

    /**
     * Returns the location that a server belongs to.
     *
     * @return \Znck\Eloquent\Relations\BelongsToThrough
     *
     * @throws \Exception
     */
    public function location()
    {
        return $this->belongsToThrough(Location::class, Node::class);
    }

    /**
     * Returns the associated server transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function transfer()
    {
        return $this->hasOne(ServerTransfer::class)->whereNull('successful')->orderByDesc('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function backups()
    {
        return $this->hasMany(Backup::class);
    }

    /**
     * Returns all mounts that have this server has mounted.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function mounts()
    {
        return $this->hasManyThrough(Mount::class, MountServer::class, 'server_id', 'id', 'id', 'mount_id');
    }

    /**
     * Returns a fresh AuditLog model for the server. This model is not saved to the
     * database when created, so it is up to the caller to correctly store it as needed.
     *
     * @return \Pterodactyl\Models\AuditLog
     */
    public function newAuditEvent(string $action, array $metadata = []): AuditLog
    {
        return AuditLog::instance($action, $metadata)->fill([
            'server_id' => $this->id,
        ]);
    }

    /**
     * Stores a new audit event for a server by using a transaction. If the transaction
     * fails for any reason everything executed within will be rolled back. The callback
     * passed in will receive the AuditLog model before it is saved and the second argument
     * will be the current server instance. The callback should modify the audit entry as
     * needed before finishing, any changes will be persisted.
     *
     * The response from the callback is returned to the caller.
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function audit(string $action, Closure $callback)
    {
        return $this->getConnection()->transaction(function () use ($action, $callback) {
            $model = $this->newAuditEvent($action);
            $response = $callback($model, $this);
            $model->save();

            return $response;
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function audits()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Checks if the server is currently in a user-accessible state. If not, an
     * exception is raised. This should be called whenever something needs to make
     * sure the server is not in a weird state that should block user access.
     *
     * @throws \Pterodactyl\Exceptions\Http\Server\ServerStateConflictException
     */
    public function validateCurrentState()
    {
        if (
            $this->isSuspended() ||
            !$this->isInstalled() ||
            $this->status === self::STATUS_RESTORING_BACKUP ||
            !is_null($this->transfer)
        ) {
            throw new ServerStateConflictException($this);
        }
    }
}
