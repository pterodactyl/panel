<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
 * @property Egg $egg
 * @property ServerVariable $serverVariable
 *
 * The "server_value" variable is only present on the object if you've loaded this model
 * using the server relationship.
 * @property string|null $server_value
 */
class EggVariable extends Model
{
    /** @use HasFactory<\Database\Factories\EggVariableFactory> */
    use HasFactory;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'egg_variable';

    /**
     * Reserved environment variable names.
     */
    public const RESERVED_ENV_NAMES = 'SERVER_MEMORY,SERVER_IP,SERVER_PORT,ENV,HOME,USER,STARTUP,SERVER_UUID,UUID';

    protected bool $immutableDates = true;

    /**
     * The table associated with the model.
     */
    protected $table = 'egg_variables';

    /**
     * Fields that are not mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'egg_id' => 'integer',
        'user_viewable' => 'bool',
        'user_editable' => 'bool',
    ];

    public static array $validationRules = [
        'egg_id' => 'exists:eggs,id',
        'name' => 'required|string|between:1,191',
        'description' => 'string',
        'env_variable' => 'required|regex:/^[\w]{1,191}$/|notIn:' . self::RESERVED_ENV_NAMES,
        'default_value' => 'string',
        'user_viewable' => 'boolean',
        'user_editable' => 'boolean',
        'rules' => 'required|string',
    ];

    protected $attributes = [
        'user_editable' => 0,
        'user_viewable' => 0,
    ];

    public function getRequiredAttribute(): bool
    {
        return in_array('required', explode('|', $this->rules));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\Pterodactyl\Models\Egg, $this>
     */
    public function egg(): HasOne
    {
        return $this->hasOne(Egg::class);
    }

    /**
     * Return server variables associated with this variable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Pterodactyl\Models\ServerVariable, $this>
     */
    public function serverVariable(): HasMany
    {
        return $this->hasMany(ServerVariable::class, 'variable_id');
    }
}
