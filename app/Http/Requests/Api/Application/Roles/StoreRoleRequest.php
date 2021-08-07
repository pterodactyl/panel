<?php

namespace Pterodactyl\Http\Requests\Api\Application\Roles;

use Pterodactyl\Models\AdminRole;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreRoleRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? AdminRole::getRules();
    }
}
