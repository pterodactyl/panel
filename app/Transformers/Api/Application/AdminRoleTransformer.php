<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\AdminRole;

class AdminRoleTransformer extends BaseTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return AdminRole::RESOURCE_NAME;
    }

    /**
     * Return a transformed User model that can be consumed by external services.
     *
     * @param \Pterodactyl\Models\AdminRole $role
     * @return array
     */
    public function transform(AdminRole $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
        ];
    }
}
