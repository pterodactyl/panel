<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nests;

use Pterodactyl\Models\Nest;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreNestRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_NESTS;
    protected int $permission = AdminAcl::WRITE;

    public function rules(array $rules = null): array
    {
        return $rules ?? Nest::getRules();
    }
}
