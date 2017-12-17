<?php

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\Node;
use Pterodactyl\Transformers\Api\ApiTransformer;

class NodeTransformer extends ApiTransformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['allocations', 'location', 'servers'];

    /**
     * Return a generic transformed pack array.
     *
     * @param \Pterodactyl\Models\Node $node
     * @return array
     */
    public function transform(Node $node): array
    {
        return $node->toArray();
    }

    /**
     * Return the nodes associated with this location.
     *
     * @param \Pterodactyl\Models\Node $node
     * @return \League\Fractal\Resource\Collection
     */
    public function includeAllocations(Node $node)
    {
        if (! $node->relationLoaded('allocations')) {
            $node->load('allocations');
        }

        return $this->collection($node->getRelation('allocations'), new AllocationTransformer($this->getRequest()), 'allocation');
    }

    /**
     * Return the nodes associated with this location.
     *
     * @param \Pterodactyl\Models\Node $node
     * @return bool|\League\Fractal\Resource\Item
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function includeLocation(Node $node)
    {
        if (! $this->authorize('location-list')) {
            return false;
        }

        if (! $node->relationLoaded('location')) {
            $node->load('location');
        }

        return $this->item($node->getRelation('location'), new LocationTransformer($this->getRequest()), 'location');
    }

    /**
     * Return the nodes associated with this location.
     *
     * @param \Pterodactyl\Models\Node $node
     * @return bool|\League\Fractal\Resource\Collection
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function includeServers(Node $node)
    {
        if (! $this->authorize('server-list')) {
            return false;
        }

        if (! $node->relationLoaded('servers')) {
            $node->load('servers');
        }

        return $this->collection($node->getRelation('servers'), new ServerTransformer($this->getRequest()), 'server');
    }
}
