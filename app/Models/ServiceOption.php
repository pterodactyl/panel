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

class ServiceOption extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_options';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'service_id' => 'integer',
        'script_is_privileged' => 'boolean',
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'service_id' => 'required',
        'name' => 'required',
        'description' => 'required',
        'tag' => 'required',
        'docker_image' => 'sometimes',
        'startup' => 'sometimes',
        'config_from' => 'sometimes',
        'config_stop' => 'required_without:config_from',
        'config_startup' => 'required_without:config_from',
        'config_logs' => 'required_without:config_from',
        'config_files' => 'required_without:config_from',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'service_id' => 'numeric|exists:services,id',
        'name' => 'string|max:255',
        'description' => 'string',
        'tag' => 'alpha_num|max:60|unique:service_options,tag',
        'docker_image' => 'string|max:255',
        'startup' => 'nullable|string',
        'config_from' => 'nullable|numeric|exists:service_options,id',
        'config_stop' => 'nullable|string|max:255',
        'config_startup' => 'nullable|json',
        'config_logs' => 'nullable|json',
        'config_files' => 'nullable|json',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'config_stop' => null,
        'config_startup' => null,
        'config_logs' => null,
        'config_files' => null,
        'startup' => null,
        'docker_image' => null,
    ];

    /**
     * Returns the display startup string for the option and will use the parent
     * service one if the option does not have one defined.
     *
     * @return string
     */
    public function getDisplayStartupAttribute($value)
    {
        return (is_null($this->startup)) ? $this->service->startup : $this->startup;
    }

    /**
     * Returns the install script for the option; if option is copying from another
     * it will return the copied script.
     *
     * @return string
     */
    public function getCopyScriptInstallAttribute($value)
    {
        return (is_null($this->copy_script_from)) ? $this->script_install : $this->copyFrom->script_install;
    }

    /**
     * Returns the entry command for the option; if option is copying from another
     * it will return the copied entry command.
     *
     * @return string
     */
    public function getCopyScriptEntryAttribute($value)
    {
        return (is_null($this->copy_script_from)) ? $this->script_entry : $this->copyFrom->script_entry;
    }

    /**
     * Returns the install container for the option; if option is copying from another
     * it will return the copied install container.
     *
     * @return string
     */
    public function getCopyScriptContainerAttribute($value)
    {
        return (is_null($this->copy_script_from)) ? $this->script_container : $this->copyFrom->script_container;
    }

    /**
     * Gets service associated with a service option.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Gets all servers associated with this service option.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class, 'option_id');
    }

    /**
     * Gets all variables associated with this service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variables()
    {
        return $this->hasMany(ServiceVariable::class, 'option_id');
    }

    /**
     * Gets all packs associated with this service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function packs()
    {
        return $this->hasMany(Pack::class, 'option_id');
    }

    /**
     * Get the parent service option from which to copy scripts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function copyFrom()
    {
        return $this->belongsTo(self::class, 'copy_script_from');
    }
}
