<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

class AllocationTransformer extends BaseAdminTransformer
{
    protected array $eagerLoads = ['server'];

    protected array $includeRelations = [
        'node' => ['relation' => 'node', 'transformer' => NodeTransformer::class],
        'server' => ['relation' => 'server', 'transformer' => ServerTransformer::class],
    ];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['node', 'server'];

    public function getResourceName(): string
    {
        return Allocation::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function transform(Allocation $allocation): array
    {
        $allocation->loadMissing('server');

        return [
            'id' => $allocation->id,
            'ip' => $allocation->ip,
            'alias' => $allocation->ip_alias,
            'port' => $allocation->port,
            'notes' => $allocation->notes,
            'server_id' => $allocation->server_id,
            'server_name' => $allocation->server?->name,
            'assigned' => ($allocation->server_id) !== null,
        ];
    }

    public function includeNode(Allocation $allocation): Item|NullResource
    {
        $allocation->loadMissing('node');

        return $this->item(
            $allocation->getRelation('node'),
            $this->makeTransformer(NodeTransformer::class),
            Node::RESOURCE_NAME
        );
    }

    public function includeServer(Allocation $allocation): Item|NullResource
    {
        $allocation->loadMissing('server');

        if (($allocation->getRelation('server')) === null) {
            return $this->null();
        }

        return $this->item(
            $allocation->getRelation('server'),
            $this->makeTransformer(ServerTransformer::class),
            Server::RESOURCE_NAME
        );
    }
}
