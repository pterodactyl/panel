<?php

namespace Pterodactyl\Http\Requests\Api\Application\Roles;

use Pterodactyl\Models\AdminRole;

class UpdateRoleRequest extends StoreRoleRequest
{
    /**
     * ?
     *
     * @param array|null $rules
     *
     * @return array
     */
    public function rules(array $rules = null): array
    {
        return $rules ?? AdminRole::getRulesForUpdate($this->route()->parameter('role')->id);
    }
}
