<?php

namespace Pterodactyl\Models;

use Schema;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Znck\Eloquent\Traits\BelongsToThrough;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Server extends Model implements CleansAttributes, ValidableContract
{
    use BelongsToThrough, Eloquence, Notifiable, Validable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'server';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'servers';

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
    protected $guarded = ['id', 'installed', self::CREATED_AT, self::UPDATED_AT, 'deleted_at'];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'external_id' => 'sometimes',
        'owner_id' => 'required',
        'name' => 'required',
        'memory' => 'required',
        'swap' => 'required',
        'io' => 'required',
        'cpu' => 'required',
        'disk' => 'required',
        'nest_id' => 'required',
        'egg_id' => 'required',
        'node_id' => 'required',
        'allocation_id' => 'required',
        'pack_id' => 'sometimes',
        'skip_scripts' => 'sometimes',
        'image' => 'required',
        'startup' => 'required',
        'database_limit' => 'present',
        'allocation_limit' => 'present',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'external_id' => 'nullable|string|between:1,191|unique:servers',
        'owner_id' => 'integer|exists:users,id',
        'name' => 'string|min:1|max:255',
        'node_id' => 'exists:nodes,id',
        'description' => 'string',
        'memory' => 'numeric|min:0',
        'swap' => 'numeric|min:-1',
        'io' => 'numeric|between:10,1000',
        'cpu' => 'numeric|min:0',
        'disk' => 'numeric|min:0',
        'allocation_id' => 'bail|unique:servers|exists:allocations,id',
        'nest_id' => 'exists:nests,id',
        'egg_id' => 'exists:eggs,id',
        'pack_id' => 'nullable|numeric|min:0',
        'startup' => 'string',
        'skip_scripts' => 'boolean',
        'image' => 'string|max:255',
        'installed' => 'boolean',
        'database_limit' => 'nullable|integer|min:0',
        'allocation_limit' => 'nullable|integer|min:0',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'node_id' => 'integer',
        'skip_scripts' => 'boolean',
        'suspended' => 'integer',
        'owner_id' => 'integer',
        'memory' => 'integer',
        'swap' => 'integer',
        'disk' => 'integer',
        'io' => 'integer',
        'cpu' => 'integer',
        'oom_disabled' => 'integer',
        'allocation_id' => 'integer',
        'nest_id' => 'integer',
        'egg_id' => 'integer',
        'pack_id' => 'integer',
        'installed' => 'integer',
        'database_limit' => 'integer',
        'allocation_limit' => 'integer',
    ];

    /**
     * Parameters for search querying.
     *
     * @var array
     */
    protected $searchableColumns = [
        'name' => 100,
        'uuid' => 80,
        'uuidShort' => 80,
        'external_id' => 50,
        'user.email' => 40,
        'user.username' => 30,
        'node.name' => 10,
        'pack.name' => 10,
    ];

    /**
     * Return the columns available for this table.
     *
     * @return array
     */
    public function getTableColumns()
    {
        return Schema::getColumnListing($this->getTable());
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
        return $this->hasMany(Subuser::class);
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
     * Gets information for the pack associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pack()
    {
        return $this->belongsTo(Pack::class);
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function egg()
    {
        return $this->belongsTo(Egg::class);
    }

    /**
     * Gets information for the service variables associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variables()
    {
        return $this->hasMany(ServerVariable::class);
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
     * Return the key belonging to the server owner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function key()
    {
        return $this->hasOne(DaemonKey::class, 'user_id', 'owner_id');
    }

    /**
     * Returns all of the daemon keys belonging to this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function keys()
    {
        return $this->hasMany(DaemonKey::class);
    }
}
