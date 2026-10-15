<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\EggVariableFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $server_value only set when loaded through Server::variables()
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
class EggVariable extends Model
{
    /** @use HasFactory<EggVariableFactory> */
    use HasFactory;

    public const string RESOURCE_NAME = 'egg_variable';

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
            'env_variable' => 'string',
            'user_viewable' => 'bool',
            'user_editable' => 'bool',
            'options_source' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Egg, $this>
     */
    public function egg(): BelongsTo
    {
        return $this->belongsTo(Egg::class);
    }

    /**
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
        return Attribute::make(get: fn (): bool => in_array('required', explode('|', $this->rules ?? '')));
    }
}
