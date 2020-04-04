<?php

namespace Pterodactyl\Models;

use Pterodactyl\Models\Traits\Searchable;

/**
 * @property int $id
 * @property int $egg_id
 * @property string $uuid
 * @property string $name
 * @property string $version
 * @property string $description
 * @property bool $selectable
 * @property bool $visible
 * @property bool $locked
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \Pterodactyl\Models\Egg|null $egg
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Server[] $servers
 */
class Pack extends Model
{
    use Searchable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'pack';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'packs';

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'egg_id', 'uuid', 'name', 'version', 'description', 'selectable', 'visible', 'locked',
    ];

    /**
     * @var array
     */
    public static $validationRules = [
        'name' => 'required|string',
        'version' => 'required|string',
        'description' => 'sometimes|nullable|string',
        'selectable' => 'sometimes|required|boolean',
        'visible' => 'sometimes|required|boolean',
        'locked' => 'sometimes|required|boolean',
        'egg_id' => 'required|exists:eggs,id',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'egg_id' => 'integer',
        'selectable' => 'boolean',
        'visible' => 'boolean',
        'locked' => 'boolean',
    ];

    /**
     * Parameters for search querying.
     *
     * @var array
     */
    protected $searchableColumns = [
        'name' => 10,
        'uuid' => 8,
        'egg.name' => 6,
        'egg.docker_image' => 5,
        'version' => 2,
    ];

    /**
     * Gets egg associated with a service pack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function egg()
    {
        return $this->belongsTo(Egg::class);
    }

    /**
     * Gets servers associated with a pack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class);
    }
}
