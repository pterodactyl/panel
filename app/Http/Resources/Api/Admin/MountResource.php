<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Models\Mount;

/**
 * @extends AdminResource<Mount>
 */
#[ResponseField('description', nullable: true)]
#[ResponseField('mounted', 'boolean', 'Present only when listed in the context of a server.', required: false, nullable: true)]
class MountResource extends AdminResource
{
    protected array $includeRelations = [
        'eggs' => ['relation' => 'eggs', 'resource' => EggResource::class],
        'nodes' => ['relation' => 'nodes', 'resource' => NodeResource::class],
        'servers' => ['relation' => 'servers', 'resource' => ServerResource::class],
    ];

    protected array $availableIncludes = ['eggs', 'nodes', 'servers'];

    public function resourceName(): string
    {
        return Mount::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        $mount = $this->resource;

        return [
            'id' => $mount->id,
            'uuid' => $mount->uuid,
            'name' => $mount->name,
            'description' => $mount->description,
            'source' => $mount->source,
            'target' => $mount->target,
            'read_only' => (bool) $mount->read_only,
            'user_mountable' => (bool) $mount->user_mountable,
            'eggs_count' => (int) ($mount->eggs_count ?? 0),
            'nodes_count' => (int) ($mount->nodes_count ?? 0),
            'servers_count' => (int) ($mount->servers_count ?? 0),
            ...$this->extensionFields($mount),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'eggs' => $this->includeEggs(),
            'nodes' => $this->includeNodes(),
            'servers' => $this->includeServers(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeEggs(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('eggs')->eggs, EggResource::class, 'egg');
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
