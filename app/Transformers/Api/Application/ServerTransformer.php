<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Support\JsonValueGuard;

#[ResponseField('description', nullable: true)]
#[ResponseField('feature_limits.databases', 'integer', example: 0, nullable: true)]
#[ResponseField('feature_limits.allocations', 'integer', example: 0, nullable: true)]
#[ResponseField('feature_limits.backups', 'integer', example: 0, nullable: true)]
#[ResponseField('container.environment', schema: ['type' => 'object', 'additionalProperties' => ['nullable' => true, 'oneOf' => [['type' => 'string'], ['type' => 'number'], ['type' => 'boolean']]], 'example' => ['SERVER_JARFILE' => 'server.jar']])]
class ServerTransformer extends BaseTransformer
{
    protected array $eagerLoads = ['egg.variables', 'serverVariables', 'location'];

    protected array $includeRelations = [
        'allocations' => ['relation' => 'allocations', 'transformer' => AllocationTransformer::class, 'ability' => AdminAcl::RESOURCE_ALLOCATIONS],
        'subusers' => ['relation' => 'subusers', 'transformer' => SubuserTransformer::class, 'ability' => AdminAcl::RESOURCE_USERS],
        'user' => ['relation' => 'user', 'transformer' => UserTransformer::class, 'ability' => AdminAcl::RESOURCE_USERS],
        'egg' => ['relation' => 'egg', 'transformer' => EggTransformer::class, 'ability' => AdminAcl::RESOURCE_EGGS],
        'location' => ['relation' => 'location', 'transformer' => LocationTransformer::class, 'ability' => AdminAcl::RESOURCE_LOCATIONS],
        'node' => ['relation' => 'node', 'transformer' => NodeTransformer::class, 'ability' => AdminAcl::RESOURCE_NODES],
        'databases' => ['relation' => 'databases', 'transformer' => ServerDatabaseTransformer::class, 'ability' => AdminAcl::RESOURCE_SERVER_DATABASES],
        'transfer' => ['relation' => 'transfer', 'transformer' => ServerTransferTransformer::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
    ];

    /**
     * List of resources that can be included.
     *
     * @var list<string>
     */
    protected array $availableIncludes = [
        'allocations',
        'user',
        'subusers',
        'egg',
        'variables',
        'location',
        'node',
        'databases',
        'transfer',
    ];

    public function __construct(private readonly EnvironmentService $environmentService) {}

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed server array.
     *
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
                // This field is deprecated, please use "status".
                'installed' => $server->isInstalled() ? 1 : 0,
                'environment' => $this->environmentService->handle($server),
                'skip_scripts' => $server->skip_scripts,
            ],
            'relationships' => [],
            $server->getUpdatedAtColumn() => $this->formatTimestamp($server->updated_at),
            $server->getCreatedAtColumn() => $this->formatTimestamp($server->created_at),
            ...$this->extensionFields($server),
        ];
        JsonValueGuard::assertPayload($payload);

        return $payload;
    }

    /**
     * Return a generic array of allocations for this server.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeAllocations(Server $server): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_ALLOCATIONS)) {
            return $this->null();
        }

        $server->loadMissing('allocations');

        return $this->collection($server->getRelation('allocations'), $this->makeTransformer(AllocationTransformer::class), 'allocation');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeSubusers(Server $server): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        $server->loadMissing('subusers');

        return $this->collection($server->getRelation('subusers'), $this->makeTransformer(SubuserTransformer::class), 'subuser');
    }

    /**
     * @throws InvalidTransformerLevelException
     */
    public function includeUser(Server $server): Item|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        $server->loadMissing('user');

        return $this->item($server->getRelation('user'), $this->makeTransformer(UserTransformer::class), 'user');
    }

    /**
     * Return a generic array with egg information for this server.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeEgg(Server $server): Item|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $server->loadMissing('egg');

        return $this->item($server->getRelation('egg'), $this->makeTransformer(EggTransformer::class), 'egg');
    }

    /**
     * @throws InvalidTransformerLevelException
     */
    public function includeVariables(Server $server): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->collection($this->environmentService->variables($server), $this->makeTransformer(ServerVariableTransformer::class), 'server_variable');
    }

    /**
     * Return a generic array with location information for this server.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeLocation(Server $server): Item|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_LOCATIONS)) {
            return $this->null();
        }

        $server->loadMissing('location');

        return $this->item($server->getRelation('location'), $this->makeTransformer(LocationTransformer::class), 'location');
    }

    /**
     * Return a generic array with node information for this server.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeNode(Server $server): Item|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        $server->loadMissing('node');

        return $this->item($server->getRelation('node'), $this->makeTransformer(NodeTransformer::class), 'node');
    }

    /**
     * Return a generic array with database information for this server.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeDatabases(Server $server): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVER_DATABASES)) {
            return $this->null();
        }

        $server->loadMissing('databases');

        return $this->collection($server->getRelation('databases'), $this->makeTransformer(ServerDatabaseTransformer::class), 'databases');
    }

    /**
     * Return the active transfer for this server, when one exists.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeTransfer(Server $server): Item|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $server->loadMissing('transfer');

        if (($server->getRelation('transfer')) === null) {
            return $this->null();
        }

        return $this->item($server->getRelation('transfer'), $this->makeTransformer(ServerTransferTransformer::class), 'server_transfer');
    }
}
