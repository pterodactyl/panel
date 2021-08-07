<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Node;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class NodeTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['allocations', 'database_host', 'location', 'mounts', 'servers'];

    public function getResourceName(): string
    {
        return Node::RESOURCE_NAME;
    }

    public function transform(Node $model): array
    {
        $response = $model->toArray();

        $response[$model->getUpdatedAtColumn()] = self::formatTimestamp($model->updated_at);
        $response[$model->getCreatedAtColumn()] = self::formatTimestamp($model->created_at);

        $resources = $model->servers()->select(['memory', 'disk'])->get();

        $response['allocated_resources'] = [
            'memory' => $resources->sum('memory'),
            'disk' => $resources->sum('disk'),
        ];

        return $response;
    }

    /**
     * Return the allocations associated with this node.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeAllocations(Node $node)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_ALLOCATIONS)) {
            return $this->null();
        }

        return $this->collection($node->allocations, new AllocationTransformer());
    }

    /**
     * Return the database host associated with this node.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeDatabaseHost(Node $node)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_DATABASE_HOSTS) || is_null($node->databaseHost)) {
            return $this->null();
        }

        return $this->item($node->databaseHost, new DatabaseHostTransformer());
    }

    /**
     * Return the location associated with this node.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeLocation(Node $node)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_LOCATIONS)) {
            return $this->null();
        }

        return $this->item($node->location, new LocationTransformer());
    }

    /**
     * Return the mounts associated with this node.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeMounts(Node $node)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_MOUNTS)) {
            return $this->null();
        }

        return $this->collection($node->mounts, new MountTransformer());
    }

    /**
     * Return the servers associated with this node.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeServers(Node $node)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->collection($node->servers, new ServerTransformer());
    }
}
