<?php

namespace Pterodactyl\Models;

use Illuminate\Validation\Rules\NotIn;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $description
 * @property string $source
 * @property string $target
 * @property bool $read_only
 * @property bool $user_mountable
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
    public const RESOURCE_NAME = 'mount';

    /**
     * The table associated with the model.
     */
    protected $table = 'mounts';

    /**
     * Fields that are not mass assignable.
     */
    protected $guarded = ['id', 'uuid'];

    /**
     * Default values for specific fields in the database.
     */
    protected $casts = [
        'id' => 'int',
        'read_only' => 'bool',
        'user_mountable' => 'bool',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'name' => 'required|string|min:2|max:64|unique:mounts,name',
        'description' => 'nullable|string|max:191',
        'source' => 'required|string',
        'target' => 'required|string',
        'read_only' => 'sometimes|boolean',
        'user_mountable' => 'sometimes|boolean',
    ];

    /**
     * Implement language verification by overriding Eloquence's gather
     * rules function.
     */
    public static function getRules(): array
    {
        $rules = parent::getRules();

        $rules['source'][] = new NotIn(Mount::$invalidSourcePaths);
        $rules['target'][] = new NotIn(Mount::$invalidTargetPaths);

        return $rules;
    }

    /**
     * Disable timestamps on this model.
     */
    public $timestamps = false;

    /**
     * Blacklisted source paths.
     */
    public static $invalidSourcePaths = [
        '/etc/pterodactyl',
        '/var/lib/pterodactyl/volumes',
        '/srv/daemon-data',
    ];

    /**
     * Blacklisted target paths.
     */
    public static $invalidTargetPaths = [
        '/home/container',
    ];

    /**
     * Returns all eggs that have this mount assigned.
     */
    public function eggs(): BelongsToMany
    {
        return $this->belongsToMany(Egg::class);
    }

    /**
     * Returns all nodes that have this mount assigned.
     */
    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(Node::class);
    }

    /**
     * Returns all servers that have this mount assigned.
     */
    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(Server::class);
    }
}
