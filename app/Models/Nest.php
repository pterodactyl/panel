<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 * @property string $uuid
 * @property string $author
 * @property string $name
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Server[] $servers
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Egg[] $eggs
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Pack[] $packs
 */
class Nest extends Model
{
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
    public static $validationRules = [
        'author' => 'required|string|email',
        'name' => 'required|string|max:255',
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
