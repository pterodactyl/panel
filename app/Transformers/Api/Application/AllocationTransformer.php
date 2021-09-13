<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class AllocationTransformer extends Transformer
{
    /**
     * Relationships that can be loaded onto allocation transformations.
     *
     * @var array
     */
    protected $availableIncludes = ['node', 'server'];

    public function getResourceName(): string
    {
        return Allocation::RESOURCE_NAME;
    }

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
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeNode(Allocation $allocation)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->item($allocation->node, new NodeTransformer());
    }

    /**
     * Load the server relationship onto a given transformation.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeServer(Allocation $allocation)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS) || !$allocation->server) {
            return $this->null();
        }

        return $this->item($allocation->server, new ServerTransformer());
    }
}
