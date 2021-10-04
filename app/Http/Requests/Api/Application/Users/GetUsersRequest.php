<?php

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Services\Acl\Api\AdminAcl as Acl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetUsersRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = Acl::RESOURCE_USERS;

    /**
     * @var int
     */
    protected $permission = Acl::READ;
}
