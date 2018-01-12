<?php

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Transformers\Api\BaseTransformer;

class AllocationTransformer extends BaseTransformer
{
    /**
     * Relationships that can be loaded onto allocation transformations.
     *
     * @var array
     */
    protected $availableIncludes = [
        'node',
        'server',
    ];

    /**
     * Return a generic transformed allocation array.
     *
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return array
     */
    public function transform(Allocation $allocation)
    {
        return [
            'id' => $allocation->id,
            'ip' => $allocation->ip,
            'alias' => $allocation->ip_alias,
            'port' => $allocation->port,
            'assigned' => ! is_null($allocation->server_id),
        ];
    }

    /**
     * Load the node relationship onto a given transformation.
     *
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return bool|\League\Fractal\Resource\Item
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function includeNode(Allocation $allocation)
    {
        if (! $this->authorize('node-view')) {
            return false;
        }

        $allocation->loadMissing('node');

        return $this->item($allocation->getRelation('node'), new NodeTransformer($this->getRequest()), 'node');
    }

    /**
     * Load the server relationship onto a given transformation.
     *
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return bool|\League\Fractal\Resource\Item
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function includeServer(Allocation $allocation)
    {
        if (! $this->authorize('server-view')) {
            return false;
        }

        $allocation->loadMissing('server');

        return $this->item($allocation->getRelation('server'), new ServerTransformer($this->getRequest()), 'server');
    }
}
