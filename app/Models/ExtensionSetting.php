<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;

/**
 * One key/value pair of an extension's settings. Values are stored JSON-encoded.
 *
 * @property int $id
 * @property string $extension
 * @property string $key
 * @property string $scope
 * @property int|null $user_id
 * @property int|null $server_id
 * @property bool $is_secret
 * @property string|null $value
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
#[Hidden(['value'])]
class ExtensionSetting extends Model
{
    /** @var array<string, string|bool> */
    protected $attributes = ['scope' => 'global', 'is_secret' => false];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_secret' => 'boolean'];
    }
}
