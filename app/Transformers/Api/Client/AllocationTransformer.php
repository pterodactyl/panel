<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Allocation;

class AllocationTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return 'allocation';
    }

    /**
     * Return basic information about the currently logged in user.
     *
     * @param \Pterodactyl\Models\Allocation $model
     * @return array
     */
    public function transform(Allocation $model)
    {
        return [
            'id' => $model->id,
            'ip' => $model->ip,
            'ip_alias' => $model->ip_alias,
            'port' => $model->port,
            'notes' => $model->notes,
            'is_default' => $model->server->allocation_id === $model->id,
        ];
    }
}
