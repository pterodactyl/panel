<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * @extends ApplicationResource<Allocation>
 */
class AllocationResource extends ApplicationResource
{
    protected array $includeRelations = [
        'node' => ['relation' => 'node', 'resource' => NodeResource::class, 'ability' => AdminAcl::RESOURCE_NODES],
        'server' => ['relation' => 'server', 'resource' => ServerResource::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
    ];

    protected array $availableIncludes = ['node', 'server'];

    public function resourceName(): string
    {
        return Allocation::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'ip' => $this->resource->ip,
            'alias' => $this->resource->ip_alias,
            'port' => $this->resource->port,
            'notes' => $this->resource->notes,
            'assigned' => $this->resource->server_id !== null,
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'node' => $this->includeNode(),
            'server' => $this->includeServer(),
            default => parent::resolveInclude($include),
        };
    }

    /**
     * @return ApiResource<Node>|NullResource
     */
    private function includeNode(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->item($this->resource->node, NodeResource::class, Node::RESOURCE_NAME);
    }

    /**
     * @return ApiResource<Server>|NullResource
     */
    private function includeServer(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $server = $this->resource->server;
        if ($server === null) {
            return $this->null();
        }

        return $this->item($server, ServerResource::class, Server::RESOURCE_NAME);
    }
}
