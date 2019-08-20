<?php

namespace App\Http\Requests\Api\Application\Users;

use App\Services\Acl\Api\AdminAcl as Acl;
use App\Http\Requests\Api\Application\ApplicationApiRequest;

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
