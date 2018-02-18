<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Nest extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'nest';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nests';

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'author' => 'required',
        'name' => 'required',
        'description' => 'sometimes',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'author' => 'string|email',
        'name' => 'string|max:255',
        'description' => 'nullable|string',
    ];

    /**
     * Gets all eggs associated with this service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eggs()
    {
        return $this->hasMany(Egg::class);
    }

    /**
     * Returns all of the packs associated with a nest, regardless of the egg.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function packs()
    {
        return $this->hasManyThrough(Pack::class, Egg::class, 'nest_id', 'egg_id');
    }

    /**
     * Gets all servers associated with this nest.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class);
    }
}
