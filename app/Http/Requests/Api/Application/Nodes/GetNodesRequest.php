<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetNodesRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_NODES;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;
}
