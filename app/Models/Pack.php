<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Pack extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

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
    protected static $applicationRules = [
        'name' => 'required',
        'version' => 'required',
        'description' => 'sometimes',
        'selectable' => 'sometimes|required',
        'visible' => 'sometimes|required',
        'locked' => 'sometimes|required',
        'egg_id' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'name' => 'string',
        'version' => 'string',
        'description' => 'nullable|string',
        'selectable' => 'boolean',
        'visible' => 'boolean',
        'locked' => 'boolean',
        'egg_id' => 'exists:eggs,id',
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
