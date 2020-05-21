<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $description
 * @property string $source
 * @property string $target
 * @property bool $read_only
 * @property bool $user_mountable
 *
 * @property \Pterodactyl\Models\Egg[]|\Illuminate\Database\Eloquent\Collection $eggs
 * @property \Pterodactyl\Models\Node[]|\Illuminate\Database\Eloquent\Collection $nodes
 * @property \Pterodactyl\Models\Server[]|\Illuminate\Database\Eloquent\Collection $servers
 */
class Mount extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'mount';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mounts';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'uuid'];

    /**
     * Default values for specific fields in the database.
     *
     * @var array
     */
    protected $attributes = [
        'id' => 'int',
        'uuid' => 'string',
        'name' => 'string',
        'description' => 'string',
        'source' => 'string',
        'target' => 'string',
        'read_only' => 'bool',
        'user_mountable' => 'bool',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     *
     * @var string
     */
    public static $validationRules = [
        // 'uuid' => 'required|string|size:36|unique:mounts,uuid',
        'name' => 'required|string|min:2|max:64|unique:mounts,name',
        'description' => 'nullable|string|max:255',
        'source' => 'required|string',
        'target' => 'required|string',
        'read_only' => 'sometimes|boolean',
        'user_mountable' => 'sometimes|boolean',
    ];

    /**
     * Disable timestamps on this model.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Returns all eggs that have this mount assigned.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function eggs()
    {
        return $this->belongsToMany(Egg::class);
    }

    /**
     * Returns all nodes that have this mount assigned.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function nodes()
    {
        return $this->belongsToMany(Node::class);
    }

    /**
     * Returns all servers that have this mount assigned.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servers()
    {
        return $this->belongsToMany(Server::class);
    }
}
