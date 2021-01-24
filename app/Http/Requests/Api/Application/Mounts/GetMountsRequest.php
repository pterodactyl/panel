<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mounts;

use Pterodactyl\Services\Acl\Api\AdminAcl as Acl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetMountsRequest extends ApplicationApiRequest
{
    protected string $resource = Acl::RESOURCE_MOUNTS;
    protected int $permission = Acl::READ;
}
