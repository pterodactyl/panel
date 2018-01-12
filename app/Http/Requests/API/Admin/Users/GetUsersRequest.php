<?php

namespace Pterodactyl\Http\Requests\API\Admin\Users;

use Pterodactyl\Services\Acl\Api\AdminAcl as Acl;
use Pterodactyl\Http\Requests\API\Admin\ApiAdminRequest;

class GetUsersRequest extends ApiAdminRequest
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
