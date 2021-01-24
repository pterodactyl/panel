<?php

namespace Pterodactyl\Http\Requests\Api\Application\Databases;

use Pterodactyl\Services\Acl\Api\AdminAcl as Acl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetDatabasesRequest extends ApplicationApiRequest
{
    protected string $resource = Acl::RESOURCE_DATABASE_HOSTS;
    protected int $permission = Acl::READ;
}
