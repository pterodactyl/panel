<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Location;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class LocationTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['nodes', 'servers'];

    public function getResourceName(): string
    {
        return Location::RESOURCE_NAME;
    }

    public function transform(Location $model): array
    {
        return [
            'id' => $model->id,
            'short' => $model->short,
            'long' => $model->long,
            $model->getUpdatedAtColumn() => $this->formatTimestamp($model->updated_at),
            $model->getCreatedAtColumn() => $this->formatTimestamp($model->created_at),
        ];
    }

    /**
     * Return the servers associated with this location.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeServers(Location $location)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->collection($location->servers, new ServerTransformer());
    }

    /**
     * Return the nodes associated with this location.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeNodes(Location $location)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->collection($location->nodes, new NodeTransformer());
    }
}
