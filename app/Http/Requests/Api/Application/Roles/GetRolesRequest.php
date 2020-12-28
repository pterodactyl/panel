<?php

namespace Pterodactyl\Http\Requests\Api\Application\Roles;

use Pterodactyl\Services\Acl\Api\AdminAcl as Acl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetRolesRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = Acl::RESOURCE_ROLES;

    /**
     * @var int
     */
    protected $permission = Acl::READ;
}
