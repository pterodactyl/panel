<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Support\JsonValueGuard;

#[ResponseField('external_id', nullable: true)]
#[ResponseField('description', nullable: true)]
#[ResponseField('feature_limits.databases', 'integer', example: 0, nullable: true)]
#[ResponseField('feature_limits.allocations', 'integer', example: 0, nullable: true)]
#[ResponseField('container.environment', schema: ['type' => 'object', 'additionalProperties' => ['nullable' => true, 'oneOf' => [['type' => 'string'], ['type' => 'number']]], 'example' => ['SERVER_JARFILE' => 'server.jar']])]
class ServerTransformer extends BaseAdminTransformer
{
    protected array $eagerLoads = ['egg.variables', 'serverVariables', 'location'];

    protected array $includeRelations = [
        'allocation' => ['relation' => 'allocation', 'transformer' => AllocationTransformer::class],
        'allocations' => ['relation' => 'allocations', 'transformer' => AllocationTransformer::class],
        'user' => ['relation' => 'user', 'transformer' => UserTransformer::class],
        'location' => ['relation' => 'location', 'transformer' => LocationTransformer::class],
        'node' => ['relation' => 'node', 'transformer' => NodeTransformer::class],
        'subusers' => ['relation' => 'subusers', 'transformer' => SubuserTransformer::class],
        'egg' => ['relation' => 'egg', 'transformer' => EggTransformer::class],
        'databases' => ['relation' => 'databases', 'transformer' => ServerDatabaseTransformer::class],
    ];

    // Only list includes with an implemented include*() method; Fractal would 500 on an unimplemented one.
    /**
     * @var list<string>
     */
    protected array $availableIncludes = [
        'allocation',
        'allocations',
        'user',
        'subusers',
        'egg',
        'variables',
        'location',
        'node',
        'databases',
    ];

    public function __construct(private readonly EnvironmentService $environmentService) {}

    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function transform(Server $server): array
    {
        $payload = [
            'id' => $server->getKey(),
            'external_id' => $server->external_id,
            'uuid' => $server->uuid,
            'identifier' => $server->uuidShort,
            'name' => $server->name,
            'description' => $server->description,
            'status' => $server->status,
            // This field is deprecated, please use "status".
            'suspended' => $server->isSuspended(),
            'limits' => [
                'memory' => $server->memory,
                'swap' => $server->swap,
                'disk' => $server->disk,
                'io' => $server->io,
                'cpu' => $server->cpu,
                'threads' => $server->threads,
                'oom_disabled' => $server->oom_disabled,
            ],
            'feature_limits' => [
                'databases' => $server->database_limit,
                'allocations' => $server->allocation_limit,
                'backups' => $server->backup_limit,
            ],
            'user' => $server->owner_id,
            'node' => $server->node_id,
            'allocation' => $server->allocation_id,
            'egg' => $server->egg_id,
            'container' => [
                'startup_command' => $server->startup,
                'image' => $server->image,
                'skip_scripts' => $server->skip_scripts,
                // Deprecated, use "status".
                'installed' => $server->isInstalled() ? 1 : 0,
                'environment' => $this->environmentService->handle($server),
            ],
            'relationships' => [],
            $server->getUpdatedAtColumn() => $this->formatTimestamp($server->updated_at),
            $server->getCreatedAtColumn() => $this->formatTimestamp($server->created_at),
            ...$this->extensionFields($server),
        ];
        JsonValueGuard::assertPayload($payload);

        return $payload;
    }

    public function includeAllocation(Server $server): Item|NullResource
    {
        $server->loadMissing('allocation');

        return $this->item(
            $server->getRelation('allocation'),
            $this->makeTransformer(AllocationTransformer::class),
            Allocation::RESOURCE_NAME
        );
    }

    public function includeAllocations(Server $server): Collection|NullResource
    {
        $server->loadMissing('allocations.server');

        return $this->collection(
            $server->getRelation('allocations'),
            $this->makeTransformer(AllocationTransformer::class),
            'allocation'
        );
    }

    public function includeUser(Server $server): Item|NullResource
    {
        $server->loadMissing('user');

        return $this->item(
            $server->getRelation('user'),
            $this->makeTransformer(UserTransformer::class),
            'user'
        );
    }

    public function includeLocation(Server $server): Item|NullResource
    {
        $server->loadMissing('location');

        return $this->item(
            $server->getRelation('location'),
            $this->makeTransformer(LocationTransformer::class),
            'location'
        );
    }

    public function includeNode(Server $server): Item|NullResource
    {
        $server->loadMissing('node');

        return $this->item(
            $server->getRelation('node'),
            $this->makeTransformer(NodeTransformer::class),
            'node'
        );
    }

    public function includeSubusers(Server $server): Collection|NullResource
    {
        $server->loadMissing('subusers');

        return $this->collection(
            $server->getRelation('subusers'),
            $this->makeTransformer(SubuserTransformer::class),
            'subuser'
        );
    }

    public function includeEgg(Server $server): Item|NullResource
    {
        $server->loadMissing('egg');

        return $this->item(
            $server->getRelation('egg'),
            $this->makeTransformer(EggTransformer::class),
            'egg'
        );
    }

    public function includeVariables(Server $server): Collection|NullResource
    {
        return $this->collection(
            $this->environmentService->variables($server),
            $this->makeTransformer(ServerVariableTransformer::class),
            'server_variable'
        );
    }

    public function includeDatabases(Server $server): Collection|NullResource
    {
        $server->loadMissing('databases');

        return $this->collection(
            $server->getRelation('databases'),
            $this->makeTransformer(ServerDatabaseTransformer::class),
            'databases'
        );
    }
}
