<?php

namespace Pterodactyl\Http\Requests\Api\Application\Roles;

use Pterodactyl\Services\Acl\Api\AdminAcl as Acl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetRolesRequest extends ApplicationApiRequest
{
    protected string $resource = Acl::RESOURCE_ROLES;
    protected int $permission = Acl::READ;
}
