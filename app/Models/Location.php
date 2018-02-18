<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Location extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'location';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'locations';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Validation rules to apply to this model.
     *
     * @var array
     */
    protected static $applicationRules = [
        'short' => 'required',
        'long' => 'required',
    ];

    /**
     * Rules ensuring that the raw data stored in the database meets expectations.
     *
     * @var array
     */
    protected static $dataIntegrityRules = [
        'short' => 'string|between:1,60|unique:locations,short',
        'long' => 'string|between:1,255',
    ];

    /**
     * Gets the nodes in a specificed location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function nodes()
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Gets the servers within a given location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function servers()
    {
        return $this->hasManyThrough(Server::class, Node::class);
    }
}
