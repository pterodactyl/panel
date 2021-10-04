<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 * @property int $egg_id
 * @property string $name
 * @property string $description
 * @property string $env_variable
 * @property string $default_value
 * @property bool $user_viewable
 * @property bool $user_editable
 * @property string $rules
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property bool $required
 * @property \Pterodactyl\Models\Egg $egg
 * @property \Pterodactyl\Models\ServerVariable $serverVariable
 *
 * The "server_value" variable is only present on the object if you've loaded this model
 * using the server relationship.
 * @property string|null $server_value
 */
class EggVariable extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'egg_variable';

    /**
     * Reserved environment variable names.
     *
     * @var string
     */
    public const RESERVED_ENV_NAMES = 'SERVER_MEMORY,SERVER_IP,SERVER_PORT,ENV,HOME,USER,STARTUP,SERVER_UUID,UUID';

    /**
     * @var bool
     */
    protected $immutableDates = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'egg_variables';

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
        'egg_id' => 'integer',
        'user_viewable' => 'bool',
        'user_editable' => 'bool',
    ];

    /**
     * @var array
     */
    public static $validationRules = [
        'egg_id' => 'exists:eggs,id',
        'name' => 'required|string|between:1,191',
        'description' => 'string',
        'env_variable' => 'required|regex:/^[\w]{1,191}$/|notIn:' . self::RESERVED_ENV_NAMES,
        'default_value' => 'string',
        'user_viewable' => 'boolean',
        'user_editable' => 'boolean',
        'rules' => 'required|string',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'user_editable' => 0,
        'user_viewable' => 0,
    ];

    /**
     * @return bool
     */
    public function getRequiredAttribute()
    {
        return in_array('required', explode('|', $this->rules));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function egg()
    {
        return $this->hasOne(Egg::class);
    }

    /**
     * Return server variables associated with this variable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function serverVariable()
    {
        return $this->hasMany(ServerVariable::class, 'variable_id');
    }
}
