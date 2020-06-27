<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class AllocationTransformer extends BaseTransformer
{
    /**
     * Relationships that can be loaded onto allocation transformations.
     *
     * @var array
     */
    protected $availableIncludes = ['node', 'server'];

    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return Allocation::RESOURCE_NAME;
    }

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
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeNode(Allocation $allocation)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        $allocation->loadMissing('node');

        return $this->item(
            $allocation->getRelation('node'), $this->makeTransformer(NodeTransformer::class), 'node'
        );
    }

    /**
     * Load the server relationship onto a given transformation.
     *
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeServer(Allocation $allocation)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $allocation->loadMissing('server');

        return $this->item(
            $allocation->getRelation('server'), $this->makeTransformer(ServerTransformer::class), 'server'
        );
    }
}
