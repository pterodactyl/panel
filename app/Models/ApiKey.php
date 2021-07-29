<?php

namespace Pterodactyl\Models;

use Pterodactyl\Services\Acl\Api\AdminAcl;

class ApiKey extends Model
{
    /**
     * Different API keys that can exist on the system.
     */
    public const TYPE_ACCOUNT = 1;
    public const TYPE_APPLICATION = 2;

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'allowed_ips' => 'array',
        'user_id' => 'int',
        'r_' . AdminAcl::RESOURCE_USERS => 'int',
        'r_' . AdminAcl::RESOURCE_ALLOCATIONS => 'int',
        'r_' . AdminAcl::RESOURCE_DATABASE_HOSTS => 'int',
        'r_' . AdminAcl::RESOURCE_SERVER_DATABASES => 'int',
        'r_' . AdminAcl::RESOURCE_EGGS => 'int',
        'r_' . AdminAcl::RESOURCE_LOCATIONS => 'int',
        'r_' . AdminAcl::RESOURCE_NESTS => 'int',
        'r_' . AdminAcl::RESOURCE_NODES => 'int',
        'r_' . AdminAcl::RESOURCE_SERVERS => 'int',
        'r_' . AdminAcl::RESOURCE_ROLES => 'int',
    ];
}
