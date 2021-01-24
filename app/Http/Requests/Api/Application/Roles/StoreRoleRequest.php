<?php

namespace Pterodactyl\Http\Requests\Api\Application\Roles;

use Pterodactyl\Models\AdminRole;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreRoleRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_ROLES;
    protected int $permission = AdminAcl::WRITE;

    public function rules(array $rules = null): array
    {
        return $rules ?? AdminRole::getRules();
    }
}
