<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\User;

class UserResourcesTransformer extends BaseTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    /**
     * Return a transformed User model that can be consumed by external services.
     */
    public function transform(User $user): array
    {
        return [
            'balance' => $user->store_balance,
            'slots' => $user->store_slots,
            'cpu' => $user->store_cpu,
            'memory' => $user->store_memory,
            'disk' => $user->store_disk,
            'ports' => $user->store_ports,
            'backups' => $user->store_backups,
            'databases' => $user->store_databases,
        ];
    }
}
