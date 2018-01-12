<?php

namespace Pterodactyl\Services\Acl\Api;

use Pterodactyl\Models\APIKey;

class AdminAcl
{
    /**
     * Resource permission columns in the api_keys table begin
     * with this identifer.
     */
    const COLUMN_IDENTIFER = 'r_';

    /**
     * The different types of permissions available for API keys. This
     * implements a read/write/none permissions scheme for all endpoints.
     */
    const NONE = 0;
    const READ = 1;
    const WRITE = 2;

    /**
     * Resources that are available on the API and can contain a permissions
     * set for each key. These are stored in the database as permission_{resource}.
     */
    const RESOURCE_SERVERS = 'servers';
    const RESOURCE_NODES = 'nodes';
    const RESOURCE_ALLOCATIONS = 'allocations';
    const RESOURCE_USERS = 'users';
    const RESOURCE_LOCATIONS = 'locations';
    const RESOURCE_NESTS = 'nests';
    const RESOURCE_EGGS = 'eggs';
    const RESOURCE_DATABASES = 'databases';
    const RESOURCE_PACKS = 'packs';

    /**
     * Determine if an API key has permission to perform a specific read/write operation.
     *
     * @param int $permission
     * @param int $action
     * @return bool
     */
    public static function can(int $permission, int $action = self::READ)
    {
        if ($permission & $action) {
            return true;
        }

        return false;
    }

    /**
     * Determine if an API Key model has permission to access a given resource
     * at a specific action level.
     *
     * @param \Pterodactyl\Models\APIKey $key
     * @param string                     $resource
     * @param int                        $action
     * @return bool
     */
    public static function check(APIKey $key, string $resource, int $action = self::READ)
    {
        return self::can(data_get($key, self::COLUMN_IDENTIFER . $resource, self::NONE), $action);
    }
}
