<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Illuminate\Support\Str;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Node;
use Pterodactyl\Support\JsonValueGuard;

#[ResponseField('servers_count', 'integer', 'Present when the endpoint loads server counts.', required: false, example: 0)]
class NodeTransformer extends BaseAdminTransformer
{
    protected array $eagerLoadSums = ['servers as sum_memory' => 'memory', 'servers as sum_disk' => 'disk'];

    protected array $includeRelations = [
        'allocations' => ['relation' => 'allocations', 'transformer' => AllocationTransformer::class],
        'location' => ['relation' => 'location', 'transformer' => LocationTransformer::class],
        'servers' => ['relation' => 'servers', 'transformer' => ServerTransformer::class],
    ];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['allocations', 'location', 'servers'];

    public function getResourceName(): string
    {
        return Node::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function transform(Node $node): array
    {
        $attributes = $node->attributesToArray();
        JsonValueGuard::assertValue($attributes);

        $response = [];
        foreach ($attributes as $key => $value) {
            // Normalize the legacy mixed-case daemonSFTP key before snake_casing.
            $key = ($key === 'daemonSFTP') ? 'daemonSftp' : $key;

            $response[Str::snake($key)] = $value;
        }

        $response[$node->getUpdatedAtColumn()] = $this->formatTimestamp($node->updated_at);
        $response[$node->getCreatedAtColumn()] = $this->formatTimestamp($node->created_at);

        unset($response['sum_memory'], $response['sum_disk']);
        $response['allocated_resources'] = $node->allocatedResources();
        $response['relationships'] = [];

        JsonValueGuard::assertPayload($response);

        return $response;
    }

    public function includeAllocations(Node $node): Collection|NullResource
    {
        $node->loadMissing('allocations.server');

        return $this->collection(
            $node->getRelation('allocations'),
            $this->makeTransformer(AllocationTransformer::class),
            'allocation'
        );
    }

    public function includeLocation(Node $node): Item|NullResource
    {
        $node->loadMissing('location');

        return $this->item(
            $node->getRelation('location'),
            $this->makeTransformer(LocationTransformer::class),
            'location'
        );
    }

    public function includeServers(Node $node): Collection|NullResource
    {
        $node->loadMissing('servers');

        return $this->collection(
            $node->getRelation('servers'),
            $this->makeTransformer(ServerTransformer::class),
            'server'
        );
    }
}
