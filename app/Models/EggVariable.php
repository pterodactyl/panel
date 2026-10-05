<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\CarbonImmutable;
use Database\Factories\EggVariableFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property bool $required
 * @property Egg $egg
 * @property ServerVariable $serverVariable
 *
 * The "server_value" variable is only present on the object if you've loaded this model
 * using the server relationship.
 * @property string|null $server_value
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
class EggVariable extends Model
{
    /** @use HasFactory<EggVariableFactory> */
    use HasFactory;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'egg_variable';

    /**
     * Reserved environment variable names.
     */
    public const string RESERVED_ENV_NAMES = 'SERVER_MEMORY,SERVER_IP,SERVER_PORT,ENV,HOME,USER,STARTUP,SERVER_UUID,UUID';

    protected $attributes = [
        'user_editable' => 0,
        'user_viewable' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'egg_id' => 'integer',
            'user_viewable' => 'bool',
            'user_editable' => 'bool',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The egg this variable is defined on.
     *
     * @return BelongsTo<Egg, $this>
     */
    public function egg(): BelongsTo
    {
        return $this->belongsTo(Egg::class);
    }

    /**
     * Return server variables associated with this variable.
     *
     * @return HasMany<ServerVariable, $this>
     */
    public function serverVariable(): HasMany
    {
        return $this->hasMany(ServerVariable::class, 'variable_id');
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function required(): Attribute
    {
        return Attribute::make(get: fn (): bool => in_array('required', explode('|', $this->rules)));
    }
}
