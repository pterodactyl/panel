<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\JsonValueGuard;

class LocationTransformer extends BaseTransformer
{
    protected array $includeRelations = [
        'servers' => ['relation' => 'servers', 'transformer' => ServerTransformer::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
        'nodes' => ['relation' => 'nodes', 'transformer' => NodeTransformer::class, 'ability' => AdminAcl::RESOURCE_NODES],
    ];

    /**
     * List of resources that can be included.
     *
     * @var list<string>
     */
    protected array $availableIncludes = ['nodes', 'servers'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Location::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed location array.
     *
     * @return ApiPayload
     */
    public function transform(Location $location): array
    {
        $payload = [
            'id' => $location->id,
            'short' => $location->short,
            'long' => $location->long,
            'relationships' => [],
            $location->getUpdatedAtColumn() => $this->formatTimestamp($location->updated_at),
            $location->getCreatedAtColumn() => $this->formatTimestamp($location->created_at),
            ...$this->extensionFields($location),
        ];
        JsonValueGuard::assertPayload($payload);

        return $payload;
    }

    /**
     * @throws InvalidTransformerLevelException
     */
    public function includeServers(Location $location): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $location->loadMissing('servers');

        return $this->collection($location->getRelation('servers'), $this->makeTransformer(ServerTransformer::class), 'server');
    }

    /**
     * Return the nodes associated with this location.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeNodes(Location $location): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        $location->loadMissing('nodes');

        return $this->collection($location->getRelation('nodes'), $this->makeTransformer(NodeTransformer::class), 'node');
    }
}
