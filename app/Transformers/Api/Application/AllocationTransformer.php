<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
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
     * @param \Pterodactyl\Models\Allocation $model
     *
     * @return array
     */
    public function transform(Allocation $model)
    {
        return [
            'id' => $model->id,
            'ip' => $model->ip,
            'alias' => $model->ip_alias,
            'port' => $model->port,
            'notes' => $model->notes,
            'assigned' => ! is_null($model->server_id),
        ];
    }

    /**
     * Load the node relationship onto a given transformation.
     *
     * @param \Pterodactyl\Models\Allocation $allocation
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeNode(Allocation $allocation)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->item(
            $allocation->node, $this->makeTransformer(NodeTransformer::class), Node::RESOURCE_NAME
        );
    }

    /**
     * Load the server relationship onto a given transformation.
     *
     * @param \Pterodactyl\Models\Allocation $allocation
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeServer(Allocation $allocation)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS) || ! $allocation->server) {
            return $this->null();
        }

        return $this->item(
            $allocation->server, $this->makeTransformer(ServerTransformer::class), Server::RESOURCE_NAME
        );
    }
}
