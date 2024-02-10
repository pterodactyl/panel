<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Node;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class NodeTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     */
    protected array $availableIncludes = ['allocations', 'location', 'servers'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Node::RESOURCE_NAME;
    }

    /**
     * Return a node transformed into a format that can be consumed by the
     * external administrative API.
     */
    public function transform(Node $model): array
    {
        $response = $model->toArray();

        $response['created_at'] = self::formatTimestamp($model->created_at);
        $response['updated_at'] = self::formatTimestamp($model->updated_at);

        $resources = $model->servers()->select(['memory', 'disk'])->get();

        $response['allocated_resources'] = [
            'memory' => $resources->sum('memory'),
            'disk' => $resources->sum('disk'),
        ];

        return $response;
    }

    /**
     * Return the allocations associated with this node.
     */
    public function includeAllocations(Node $node): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_ALLOCATIONS)) {
            return $this->null();
        }

        return $this->collection($node->allocations, new AllocationTransformer());
    }

    /**
     * Return the location associated with this node.
     */
    public function includeLocation(Node $node): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_LOCATIONS)) {
            return $this->null();
        }

        return $this->item($node->location, new LocationTransformer());
    }

    /**
     * Return the servers associated with this node.
     */
    public function includeServers(Node $node): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->collection($node->servers, new ServerTransformer());
    }
}
