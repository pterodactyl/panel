<?php

namespace Pterodactyl\Models;

class EggVariable extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'egg_variable';

    /**
     * Reserved environment variable names.
     *
     * @var string
     */
    const RESERVED_ENV_NAMES = 'SERVER_MEMORY,SERVER_IP,SERVER_PORT,ENV,HOME,USER,STARTUP,SERVER_UUID,UUID';

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
        'user_viewable' => 'integer',
        'user_editable' => 'integer',
    ];

    /**
     * @var array
     */
    public static $validationRules = [
        'egg_id' => 'exists:eggs,id',
        'name' => 'required|string|between:1,255',
        'description' => 'string',
        'env_variable' => 'required|regex:/^[\w]{1,255}$/|notIn:' . self::RESERVED_ENV_NAMES,
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
     * @param $value
     * @return bool
     */
    public function getRequiredAttribute($value)
    {
        return $this->rules === 'required' || str_contains($this->rules, ['required|', '|required']);
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
