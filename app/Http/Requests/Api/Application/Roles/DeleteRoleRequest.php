<?php

namespace Pterodactyl\Http\Requests\Api\Application\Roles;

use Pterodactyl\Models\AdminRole;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteRoleRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_ROLES;
    protected int $permission = AdminAcl::WRITE;

    public function resourceExists(): bool
    {
        $role = $this->route()->parameter('role');

        return $role instanceof AdminRole && $role->exists;
    }
}
