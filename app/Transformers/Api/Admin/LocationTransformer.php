<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Models\Location;
use Pterodactyl\Support\JsonValueGuard;

class LocationTransformer extends BaseAdminTransformer
{
    protected array $eagerLoadCounts = ['nodes', 'servers'];

    protected array $includeRelations = [
        'nodes' => ['relation' => 'nodes', 'transformer' => NodeTransformer::class],
        'servers' => ['relation' => 'servers', 'transformer' => ServerTransformer::class],
    ];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['nodes', 'servers'];

    public function getResourceName(): string
    {
        return Location::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function transform(Location $location): array
    {
        $payload = [
            'id' => $location->id,
            'short' => $location->short,
            'long' => $location->long,
            'nodes_count' => $location->nodes_count ?? $location->nodes()->count(),
            'servers_count' => $location->servers_count ?? $location->servers()->count(),
            'created_at' => $this->formatTimestamp($location->created_at),
            'updated_at' => $this->formatTimestamp($location->updated_at),
            ...$this->extensionFields($location),
        ];
        JsonValueGuard::assertPayload($payload);

        return $payload;
    }

    public function includeNodes(Location $location): Collection|NullResource
    {
        $location->loadMissing('nodes');

        return $this->collection(
            $location->getRelation('nodes'),
            $this->makeTransformer(NodeTransformer::class),
            'node'
        );
    }

    public function includeServers(Location $location): Collection|NullResource
    {
        $location->loadMissing('servers');

        return $this->collection(
            $location->getRelation('servers'),
            $this->makeTransformer(ServerTransformer::class),
            'server'
        );
    }
}
