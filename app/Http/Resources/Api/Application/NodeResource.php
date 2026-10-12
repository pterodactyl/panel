<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * @extends ApplicationResource<Node>
 */
class NodeResource extends ApplicationResource
{
    protected array $eagerLoadSums = ['servers as sum_memory' => 'memory', 'servers as sum_disk' => 'disk'];

    protected array $includeRelations = [
        'allocations' => ['relation' => 'allocations', 'resource' => AllocationResource::class, 'ability' => AdminAcl::RESOURCE_ALLOCATIONS],
        'location' => ['relation' => 'location', 'resource' => LocationResource::class, 'ability' => AdminAcl::RESOURCE_LOCATIONS],
        'servers' => ['relation' => 'servers', 'resource' => ServerResource::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
    ];

    protected array $availableIncludes = ['allocations', 'location', 'servers'];

    public function resourceName(): string
    {
        return Node::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        $node = $this->resource;

        $response = [];
        foreach ($node->attributesToArray() as $key => $value) {
            // Normalize the legacy mixed-case daemonSFTP key before snake_casing.
            $key = ($key === 'daemonSFTP') ? 'daemonSftp' : $key;

            $response[Str::snake($key)] = $value;
        }

        $response[$node->getUpdatedAtColumn()] = $this->formatTimestamp($node->updated_at);
        $response[$node->getCreatedAtColumn()] = $this->formatTimestamp($node->created_at);

        unset($response['sum_memory'], $response['sum_disk']);
        $response['allocated_resources'] = $node->allocatedResources();
        $response['relationships'] = [];

        return [...$response, ...$this->extensionFields($node)];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'allocations' => $this->includeAllocations(),
            'location' => $this->includeLocation(),
            'servers' => $this->includeServers(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeAllocations(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_ALLOCATIONS)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('allocations')->allocations, AllocationResource::class, 'allocation');
    }

    /**
     * @return ApiResource<Location>|NullResource
     */
    private function includeLocation(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_LOCATIONS)) {
            return $this->null();
        }

        return $this->item($this->resource->loadMissing('location')->location, LocationResource::class, 'location');
    }

    private function includeServers(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('servers')->servers, ServerResource::class, 'server');
    }
}
