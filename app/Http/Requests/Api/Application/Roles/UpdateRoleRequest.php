<?php

namespace Pterodactyl\Http\Requests\Api\Application\Roles;

use Pterodactyl\Models\AdminRole;

class UpdateRoleRequest extends StoreRoleRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? AdminRole::getRulesForUpdate($this->route()->parameter('role'));
    }
}
