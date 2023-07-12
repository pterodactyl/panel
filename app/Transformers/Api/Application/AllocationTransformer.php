<?php

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Item;
use Pterodactyl\Models\Allocation;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class AllocationTransformer extends Transformer
{
    protected array $availableIncludes = ['node', 'server'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Allocation::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed allocation array.
     */
    public function transform(Allocation $model): array
    {
        return [
            'id' => $model->id,
            'ip' => $model->ip,
            'alias' => $model->ip_alias,
            'port' => $model->port,
            'notes' => $model->notes,
            'server_id' => $model->server_id,
            'assigned' => !is_null($model->server_id),
        ];
    }

    /**
     * Load the node relationship onto a given transformation.
     */
    public function includeNode(Allocation $allocation): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->item($allocation->node, new NodeTransformer());
    }

    /**
     * Load the server relationship onto a given transformation.
     */
    public function includeServer(Allocation $allocation): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS) || !$allocation->server) {
            return $this->null();
        }

        return $this->item($allocation->server, new ServerTransformer());
    }
}
