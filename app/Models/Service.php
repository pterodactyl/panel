<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Service extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'services';

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'startup',
        'index_file',
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'author' => 'required',
        'name' => 'required',
        'description' => 'sometimes',
        'startup' => 'sometimes',
        'index_file' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'author' => 'email',
        'name' => 'string|max:255',
        'description' => 'nullable|string',
        'startup' => 'nullable|string',
        'index_file' => 'string',
    ];

    /**
     * Gets all service options associated with this service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
        return $this->hasMany(ServiceOption::class);
    }

    /**
     * Returns all of the packs associated with a service, regardless of the service option.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function packs()
    {
        return $this->hasManyThrough(Pack::class, ServiceOption::class, 'service_id', 'option_id');
    }

    /**
     * Gets all servers associated with this service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class);
    }
}
