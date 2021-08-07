<?php

namespace Pterodactyl\Services\Acl\Api;

class AdminAcl
{
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
    public const RESOURCE_ROLES = 'roles';
    public const RESOURCE_MOUNTS = 'mounts';
}
