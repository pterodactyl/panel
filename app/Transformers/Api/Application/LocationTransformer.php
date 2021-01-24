<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Location;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class LocationTransformer extends BaseTransformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['nodes', 'servers'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Location::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed location array.
     */
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
     * Return the nodes associated with this location.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeServers(Location $location)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $location->loadMissing('servers');

        return $this->collection($location->getRelation('servers'), $this->makeTransformer(ServerTransformer::class), 'server');
    }

    /**
     * Return the nodes associated with this location.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException

     */
    public function includeNodes(Location $location)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        $location->loadMissing('nodes');

        return $this->collection($location->getRelation('nodes'), $this->makeTransformer(NodeTransformer::class), 'node');
    }
}
