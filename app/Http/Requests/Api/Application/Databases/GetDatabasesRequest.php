<?php

namespace Pterodactyl\Http\Requests\Api\Application\Databases;

use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class GetDatabasesRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_DATABASE_HOSTS;

    protected int $permission = AdminAcl::READ;
}
