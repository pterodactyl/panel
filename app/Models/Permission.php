<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Support\Collection;

#[Guarded(['id', 'created_at', 'updated_at'])]
#[WithoutTimestamps]
class Permission extends Model
{
    public const string RESOURCE_NAME = 'subuser_permission';

    /**
     * All the permissions available on the system. You should use self::permissions()
     * to retrieve them, and not directly access this array as it is subject to change.
     *
     * @see Permission::permissions()
     *
     * @var array<string, list<string>>
     */
    protected static array $permissions = [
        'websocket' => ['connect'],
        'control' => ['console', 'start', 'stop', 'restart'],
        'user' => ['create', 'read', 'update', 'delete'],
        'file' => ['create', 'read', 'read-content', 'update', 'delete', 'archive', 'sftp'],
        'backup' => ['create', 'read', 'delete', 'download', 'restore'],
        'allocation' => ['read', 'create', 'update', 'delete'],
        'startup' => ['read', 'update', 'docker-image'],
        'database' => ['create', 'read', 'update', 'delete', 'view_password'],
        'schedule' => ['create', 'read', 'update', 'delete'],
        'settings' => ['rename', 'reinstall'],
        'activity' => ['read'],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subuser_id' => 'integer',
        ];
    }

    /**
     * @return Collection<string, array{description: string, keys: array<string, string>}>
     */
    public static function permissions(): Collection
    {
        return Collection::make(self::$permissions)->map(fn (array $keys, string $group): array => [
            'description' => self::translation("permissions.{$group}.description"),
            'keys' => array_combine($keys, array_map(fn (string $key): string => self::translation("permissions.{$group}.keys.{$key}"), $keys)),
        ]);
    }

    private static function translation(string $key): string
    {
        return __($key);
    }
}
