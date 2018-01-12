<?php

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\Location;
use Pterodactyl\Transformers\Api\BaseTransformer;

class LocationTransformer extends BaseTransformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['nodes', 'servers'];

    /**
     * Return a generic transformed pack array.
     *
     * @param \Pterodactyl\Models\Location $location
     * @return array
     */
    public function transform(Location $location): array
    {
        return $location->toArray();
    }

    /**
     * Return the nodes associated with this location.
     *
     * @param \Pterodactyl\Models\Location $location
     * @return bool|\League\Fractal\Resource\Collection
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function includeServers(Location $location)
    {
        if (! $this->authorize('server-list')) {
            return false;
        }

        if (! $location->relationLoaded('servers')) {
            $location->load('servers');
        }

        return $this->collection($location->getRelation('servers'), new ServerTransformer($this->getRequest()), 'server');
    }

    /**
     * Return the nodes associated with this location.
     *
     * @param \Pterodactyl\Models\Location $location
     * @return bool|\League\Fractal\Resource\Collection
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function includeNodes(Location $location)
    {
        if (! $this->authorize('node-list')) {
            return false;
        }

        if (! $location->relationLoaded('nodes')) {
            $location->load('nodes');
        }

        return $this->collection($location->getRelation('nodes'), new NodeTransformer($this->getRequest()), 'node');
    }
}
