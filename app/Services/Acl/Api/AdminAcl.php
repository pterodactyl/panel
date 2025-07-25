<?php

namespace Pterodactyl\Services\Acl\Api;

use Pterodactyl\Models\ApiKey;

class AdminAcl
{
    /**
     * Resource permission columns in the api_keys table begin
     * with this identifier.
     */
    public const COLUMN_IDENTIFIER = 'r_';

    /**
     * The different types of permissions available for API keys. This
     * implements a read/write/none permissions scheme for all endpoints.
     */
    public const NONE = 0;
    public const READ = 1;
    public const WRITE = 2;

    /**
     * Resources that are available on the API and can contain a permissions
     * set for each key. These are stored in the database as r_{resource}.
     */
    public const RESOURCE_SERVERS = 'servers';
    public const RESOURCE_NODES = 'nodes';
    public const RESOURCE_ALLOCATIONS = 'allocations';
    public const RESOURCE_USERS = 'users';
    public const RESOURCE_LOCATIONS = 'locations';
    public const RESOURCE_NESTS = 'nests';
    public const RESOURCE_EGGS = 'eggs';
    public const RESOURCE_DATABASE_HOSTS = 'database_hosts';
    public const RESOURCE_SERVER_DATABASES = 'server_databases';

    /**
     * Determine if an API key has permission to perform a specific read/write operation.
     */
    public static function can(int $permission, int $action = self::READ): bool
    {
        if ($permission & $action) {
            return true;
        }

        return false;
    }

    /**
     * Determine if an API Key model has permission to access a given resource
     * at a specific action level.
     */
    public static function check(ApiKey $key, string $resource, int $action = self::READ): bool
    {
        return self::can(data_get($key, self::COLUMN_IDENTIFIER . $resource, self::NONE), $action);
    }

    /**
     * Return a list of all resource constants defined in this ACL.
     *
     * @throws \ReflectionException
     */
    public static function getResourceList(): array
    {
        $reflect = new \ReflectionClass(__CLASS__);

        return collect($reflect->getConstants())->filter(function ($value, $key) {
            return substr($key, 0, 9) === 'RESOURCE_';
        })->values()->toArray();
    }
}
