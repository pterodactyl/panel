<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use Illuminate\Support\Str;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\JsonValueGuard;

class NodeTransformer extends BaseTransformer
{
    protected array $eagerLoadSums = ['servers as sum_memory' => 'memory', 'servers as sum_disk' => 'disk'];

    protected array $includeRelations = [
        'allocations' => ['relation' => 'allocations', 'transformer' => AllocationTransformer::class, 'ability' => AdminAcl::RESOURCE_ALLOCATIONS],
        'location' => ['relation' => 'location', 'transformer' => LocationTransformer::class, 'ability' => AdminAcl::RESOURCE_LOCATIONS],
        'servers' => ['relation' => 'servers', 'transformer' => ServerTransformer::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
    ];

    /**
     * List of resources that can be included.
     *
     * @var list<string>
     */
    protected array $availableIncludes = ['allocations', 'location', 'servers'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Node::RESOURCE_NAME;
    }

    /**
     * Return a node transformed into a format that can be consumed by the
     * external administrative API.
     *
     * @return ApiPayload
     */
    public function transform(Node $node): array
    {
        $attributes = $node->attributesToArray();
        JsonValueGuard::assertValue($attributes);

        $response = [];
        foreach ($attributes as $key => $value) {
            // I messed up early in 2016 when I named this column as poorly
            // as I did. This is the tragic result of my mistakes.
            $key = ($key === 'daemonSFTP') ? 'daemonSftp' : $key;

            $response[Str::snake($key)] = $value;
        }

        $response[$node->getUpdatedAtColumn()] = $this->formatTimestamp($node->updated_at);
        $response[$node->getCreatedAtColumn()] = $this->formatTimestamp($node->created_at);

        unset($response['sum_memory'], $response['sum_disk']);
        $response['allocated_resources'] = $node->allocatedResources();
        $response['relationships'] = [];
        $response = [...$response, ...$this->extensionFields($node)];

        JsonValueGuard::assertPayload($response);

        return $response;
    }

    /**
     * @throws InvalidTransformerLevelException
     */
    public function includeAllocations(Node $node): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_ALLOCATIONS)) {
            return $this->null();
        }

        $node->loadMissing('allocations');

        return $this->collection(
            $node->getRelation('allocations'),
            $this->makeTransformer(AllocationTransformer::class),
            'allocation'
        );
    }

    /**
     * @throws InvalidTransformerLevelException
     */
    public function includeLocation(Node $node): Item|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_LOCATIONS)) {
            return $this->null();
        }

        $node->loadMissing('location');

        return $this->item(
            $node->getRelation('location'),
            $this->makeTransformer(LocationTransformer::class),
            'location'
        );
    }

    /**
     * @throws InvalidTransformerLevelException
     */
    public function includeServers(Node $node): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $node->loadMissing('servers');

        return $this->collection(
            $node->getRelation('servers'),
            $this->makeTransformer(ServerTransformer::class),
            'server'
        );
    }
}
