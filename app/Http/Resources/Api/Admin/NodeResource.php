<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;

/**
 * @extends AdminResource<Node>
 */
#[ResponseField('servers_count', 'integer', 'Present when the endpoint loads server counts.', required: false, example: 0)]
class NodeResource extends AdminResource
{
    protected array $eagerLoadSums = ['servers as sum_memory' => 'memory', 'servers as sum_disk' => 'disk'];

    protected array $includeRelations = [
        'allocations' => ['relation' => 'allocations', 'resource' => AllocationResource::class],
        'location' => ['relation' => 'location', 'resource' => LocationResource::class],
        'servers' => ['relation' => 'servers', 'resource' => ServerResource::class],
        'tags' => ['relation' => 'eggTags', 'resource' => TagResource::class],
        'deployment_tags' => ['relation' => 'deploymentTags', 'resource' => TagResource::class],
    ];

    protected array $availableIncludes = ['allocations', 'location', 'servers', 'tags', 'deployment_tags'];

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
            'tags' => $this->includeTags(),
            'deployment_tags' => $this->includeDeploymentTags(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeAllocations(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('allocations.server')->allocations, AllocationResource::class, 'allocation');
    }

    /**
     * @return ApiResource<Location>
     */
    private function includeLocation(): ApiResource
    {
        return $this->item($this->resource->loadMissing('location')->location, LocationResource::class, 'location');
    }

    private function includeServers(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('servers')->servers, ServerResource::class, 'server');
    }

    private function includeTags(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('eggTags')->eggTags, TagResource::class, Tag::RESOURCE_NAME);
    }

    private function includeDeploymentTags(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('deploymentTags')->deploymentTags, TagResource::class, Tag::RESOURCE_NAME);
    }
}
