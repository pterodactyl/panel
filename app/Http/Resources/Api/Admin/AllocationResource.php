<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

/**
 * @extends AdminResource<Allocation>
 */
class AllocationResource extends AdminResource
{
    protected array $eagerLoads = ['server'];

    protected array $includeRelations = [
        'node' => ['relation' => 'node', 'resource' => NodeResource::class],
        'server' => ['relation' => 'server', 'resource' => ServerResource::class],
    ];

    protected array $availableIncludes = ['node', 'server'];

    public function resourceName(): string
    {
        return Allocation::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('server');

        return [
            'id' => $this->resource->id,
            'ip' => $this->resource->ip,
            'alias' => $this->resource->ip_alias,
            'port' => $this->resource->port,
            'notes' => $this->resource->notes,
            'server_id' => $this->resource->server_id,
            'server_name' => $this->resource->server?->name,
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
     * @return ApiResource<Node>
     */
    private function includeNode(): ApiResource
    {
        return $this->item($this->resource->loadMissing('node')->node, NodeResource::class, Node::RESOURCE_NAME);
    }

    /**
     * @return ApiResource<Server>|NullResource
     */
    private function includeServer(): ApiResource|NullResource
    {
        $server = $this->resource->loadMissing('server')->server;
        if ($server === null) {
            return $this->null();
        }

        return $this->item($server, ServerResource::class, Server::RESOURCE_NAME);
    }
}
