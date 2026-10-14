<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * @extends ApplicationResource<Location>
 */
class LocationResource extends ApplicationResource
{
    protected array $includeRelations = [
        'servers' => ['relation' => 'servers', 'resource' => ServerResource::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
        'nodes' => ['relation' => 'nodes', 'resource' => NodeResource::class, 'ability' => AdminAcl::RESOURCE_NODES],
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
            'relationships' => [],
            $location->getUpdatedAtColumn() => $this->formatTimestamp($location->updated_at),
            $location->getCreatedAtColumn() => $this->formatTimestamp($location->created_at),
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

    private function includeServers(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('servers')->servers, ServerResource::class, 'server');
    }

    private function includeNodes(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('nodes')->nodes, NodeResource::class, 'node');
    }
}
