<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class AllocationTransformer extends BaseTransformer
{
    protected array $includeRelations = [
        'node' => ['relation' => 'node', 'transformer' => NodeTransformer::class, 'ability' => AdminAcl::RESOURCE_NODES],
        'server' => ['relation' => 'server', 'transformer' => ServerTransformer::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
    ];

    /**
     * Relationships that can be loaded onto allocation transformations.
     *
     * @var list<string>
     */
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
     *
     * @return ApiPayload
     */
    public function transform(Allocation $allocation): array
    {
        return [
            'id' => $allocation->id,
            'ip' => $allocation->ip,
            'alias' => $allocation->ip_alias,
            'port' => $allocation->port,
            'notes' => $allocation->notes,
            'assigned' => ($allocation->server_id) !== null,
        ];
    }

    /**
     * Load the node relationship onto a given transformation.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeNode(Allocation $allocation): Item|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->item(
            $allocation->node,
            $this->makeTransformer(NodeTransformer::class),
            Node::RESOURCE_NAME
        );
    }

    /**
     * Load the server relationship onto a given transformation.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeServer(Allocation $allocation): Item|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS) || ! $allocation->server) {
            return $this->null();
        }

        return $this->item(
            $allocation->server,
            $this->makeTransformer(ServerTransformer::class),
            Server::RESOURCE_NAME
        );
    }
}
