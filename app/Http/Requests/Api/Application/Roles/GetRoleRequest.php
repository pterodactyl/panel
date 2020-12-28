<?php

namespace Pterodactyl\Http\Requests\Api\Application\Roles;

use Pterodactyl\Models\AdminRole;

class GetRoleRequest extends GetRolesRequest
{
    /**
     * Determine if the requested role exists on the Panel.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $role = $this->route()->parameter('role');

        return $role instanceof AdminRole && $role->exists;
    }
}
