<?php

namespace Pterodactyl\Transformers\Api\Client\Store;

use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;

class UserTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    /**
     * Transforms a User model into a representation that can be shown to regular
     * users of the API.
     */
    public function transform(User $model): array
    {
        return [
            'balance' => $model->store_balance,
            'cpu' => $model->store_cpu,
            'memory' => $model->store_memory,
            'disk' => $model->store_disk,
            'slots' => $model->store_slots,
            'ports' => $model->store_ports,
            'backups' => $model->store_backups,
            'databases' => $model->store_databases,
        ];
    }
}
