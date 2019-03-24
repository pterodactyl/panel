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
        $model->loadMissing('server');

        return [
            'ip' => $model->ip,
            'alias' => $model->ip_alias,
            'port' => $model->port,
            'default' => $model->getRelation('server')->allocation_id === $model->id,
        ];
    }
}
