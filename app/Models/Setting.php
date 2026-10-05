<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;

/**
 * Pterodactyl\Models\Setting.
 *
 * @property int $id
 * @property string $key
 * @property string $value
 */
#[Fillable(['key', 'value'])]
#[WithoutTimestamps]
class Setting extends Model
{
    /**
     * Store a persistent setting, replacing any value already stored for the key.
     */
    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value ?? '']);
    }

    /**
     * Retrieve a persistent setting, or null when nothing is stored for the key.
     */
    public static function fetch(string $key): ?string
    {
        return static::query()->where('key', $key)->first()?->value;
    }
}
