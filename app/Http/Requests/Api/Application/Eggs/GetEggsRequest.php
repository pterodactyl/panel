<?php

namespace Pterodactyl\Http\Requests\Api\Application\Eggs;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetEggsRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_EGGS;
    protected int $permission = AdminAcl::READ;
}
