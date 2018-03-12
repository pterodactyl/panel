<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Egg extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'egg';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eggs';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'docker_image',
        'config_files',
        'config_startup',
        'config_logs',
        'config_stop',
        'config_from',
        'startup',
        'script_is_privileged',
        'script_install',
        'script_entry',
        'script_container',
        'copy_script_from',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'nest_id' => 'integer',
        'config_from' => 'integer',
        'script_is_privileged' => 'boolean',
        'copy_script_from' => 'integer',
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'nest_id' => 'required',
        'uuid' => 'required',
        'name' => 'required',
        'description' => 'required',
        'author' => 'required',
        'docker_image' => 'required',
        'startup' => 'required',
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
        'nest_id' => 'bail|numeric|exists:nests,id',
        'uuid' => 'string|size:36',
        'name' => 'string|max:255',
        'description' => 'string',
        'author' => 'string|email',
        'docker_image' => 'string|max:255',
        'startup' => 'nullable|string',
        'config_from' => 'bail|nullable|numeric|exists:eggs,id',
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
    ];

    /**
     * Returns the install script for the egg; if egg is copying from another
     * it will return the copied script.
     *
     * @return string
     */
    public function getCopyScriptInstallAttribute()
    {
        if (! is_null($this->script_install) || is_null($this->copy_script_from)) {
            return $this->script_install;
        }

        return $this->scriptFrom->script_install;
    }

    /**
     * Returns the entry command for the egg; if egg is copying from another
     * it will return the copied entry command.
     *
     * @return string
     */
    public function getCopyScriptEntryAttribute()
    {
        if (! is_null($this->script_entry) || is_null($this->copy_script_from)) {
            return $this->script_entry;
        }

        return $this->scriptFrom->script_entry;
    }

    /**
     * Returns the install container for the egg; if egg is copying from another
     * it will return the copied install container.
     *
     * @return string
     */
    public function getCopyScriptContainerAttribute()
    {
        if (! is_null($this->script_container) || is_null($this->copy_script_from)) {
            return $this->script_container;
        }

        return $this->scriptFrom->script_container;
    }

    /**
     * Return the file configuration for an egg.
     *
     * @return string
     */
    public function getInheritConfigFilesAttribute()
    {
        if (! is_null($this->config_files) || is_null($this->config_from)) {
            return $this->config_files;
        }

        return $this->configFrom->config_files;
    }

    /**
     * Return the startup configuration for an egg.
     *
     * @return string
     */
    public function getInheritConfigStartupAttribute()
    {
        if (! is_null($this->config_startup) || is_null($this->config_from)) {
            return $this->config_startup;
        }

        return $this->configFrom->config_startup;
    }

    /**
     * Return the log reading configuration for an egg.
     *
     * @return string
     */
    public function getInheritConfigLogsAttribute()
    {
        if (! is_null($this->config_logs) || is_null($this->config_from)) {
            return $this->config_logs;
        }

        return $this->configFrom->config_logs;
    }

    /**
     * Return the stop command configuration for an egg.
     *
     * @return string
     */
    public function getInheritConfigStopAttribute()
    {
        if (! is_null($this->config_stop) || is_null($this->config_from)) {
            return $this->config_stop;
        }

        return $this->configFrom->config_stop;
    }

    /**
     * Gets nest associated with an egg.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nest()
    {
        return $this->belongsTo(Nest::class);
    }

    /**
     * Gets all servers associated with this egg.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class, 'egg_id');
    }

    /**
     * Gets all variables associated with this egg.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variables()
    {
        return $this->hasMany(EggVariable::class, 'egg_id');
    }

    /**
     * Gets all packs associated with this egg.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function packs()
    {
        return $this->hasMany(Pack::class, 'egg_id');
    }

    /**
     * Get the parent egg from which to copy scripts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function scriptFrom()
    {
        return $this->belongsTo(self::class, 'copy_script_from');
    }

    /**
     * Get the parent egg from which to copy configuration settings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function configFrom()
    {
        return $this->belongsTo(self::class, 'config_from');
    }
}
