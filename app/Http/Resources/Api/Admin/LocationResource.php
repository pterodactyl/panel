<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Models\Location;

/**
 * @extends AdminResource<Location>
 */
class LocationResource extends AdminResource
{
    protected array $eagerLoadCounts = ['nodes', 'servers'];

    protected array $includeRelations = [
        'nodes' => ['relation' => 'nodes', 'resource' => NodeResource::class],
        'servers' => ['relation' => 'servers', 'resource' => ServerResource::class],
    ];

    protected array $availableIncludes = ['nodes', 'servers'];

    public function resourceName(): string
    {
        return Location::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        $location = $this->resource;

        return [
            'id' => $location->id,
            'short' => $location->short,
            'long' => $location->long,
            'nodes_count' => $location->nodes_count ?? $location->nodes()->count(),
            'servers_count' => $location->servers_count ?? $location->servers()->count(),
            'created_at' => $this->formatTimestamp($location->created_at),
            'updated_at' => $this->formatTimestamp($location->updated_at),
            ...$this->extensionFields($location),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'nodes' => $this->includeNodes(),
            'servers' => $this->includeServers(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeNodes(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('nodes')->nodes, NodeResource::class, 'node');
    }

    private function includeServers(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('servers')->servers, ServerResource::class, 'server');
    }
}
