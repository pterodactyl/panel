<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Acl\Api;

use Illuminate\Support\Fluent;
use Pterodactyl\Models\ApiKey;
use ReflectionClass;
use ReflectionException;

class AdminAcl
{
    /**
     * Resource permission columns in the api_keys table begin
     * with this identifier.
     */
    public const string COLUMN_IDENTIFIER = 'r_';

    /**
     * The different types of permissions available for API keys. This
     * implements a read/write/none permissions scheme for all endpoints.
     */
    public const int NONE = 0;

    public const int READ = 1;

    public const int WRITE = 2;

    /**
     * Resources that are available on the API and can contain a permissions
     * set for each key. These are stored in the database as r_{resource}.
     */
    public const string RESOURCE_SERVERS = 'servers';

    public const string RESOURCE_NODES = 'nodes';

    public const string RESOURCE_ALLOCATIONS = 'allocations';

    public const string RESOURCE_USERS = 'users';

    public const string RESOURCE_LOCATIONS = 'locations';

    public const string RESOURCE_EGGS = 'eggs';

    public const string RESOURCE_DATABASE_HOSTS = 'database_hosts';

    public const string RESOURCE_SERVER_DATABASES = 'server_databases';

    /**
     * Determine if an API key has permission to perform a specific read/write operation.
     * Asking for no action is denied, so a request that never set one fails closed.
     */
    public static function can(int $permission, int $action = self::READ): bool
    {
        return $action !== self::NONE && ($permission & $action) === $action;
    }

    /**
     * Determine if an API Key model has permission to access a given resource
     * at a specific action level.
     */
    public static function check(ApiKey $key, string $resource, int $action = self::READ): bool
    {
        return self::can(Fluent::make($key->getAttributes())->integer(self::COLUMN_IDENTIFIER.$resource, self::NONE), $action);
    }

    /**
     * Return a list of all resource constants defined in this ACL.
     *
     * @return list<string>
     *
     * @throws ReflectionException
     */
    public static function getResourceList(): array
    {
        $reflect = new ReflectionClass(self::class);

        $resources = [];
        foreach ($reflect->getConstants() as $key => $value) {
            if (str_starts_with($key, 'RESOURCE_') && is_string($value)) {
                $resources[] = $value;
            }
        }

        return $resources;
    }
}
