<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nests;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetNestsRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_NESTS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;
}
