<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Services\Servers\EnvironmentService;
use UnexpectedValueException;

/**
 * @extends ApplicationResource<Server>
 */
#[ResponseField('description', nullable: true)]
#[ResponseField('feature_limits.databases', 'integer', example: 0, nullable: true)]
#[ResponseField('feature_limits.allocations', 'integer', example: 0, nullable: true)]
#[ResponseField('feature_limits.backups', 'integer', example: 0, nullable: true)]
#[ResponseField('container.environment', schema: ['type' => 'object', 'additionalProperties' => ['nullable' => true, 'oneOf' => [['type' => 'string'], ['type' => 'number'], ['type' => 'boolean']]], 'example' => ['SERVER_JARFILE' => 'server.jar']])]
class ServerResource extends ApplicationResource
{
    protected array $eagerLoads = ['egg.variables', 'serverVariables', 'location'];

    protected array $includeRelations = [
        'allocations' => ['relation' => 'allocations', 'resource' => AllocationResource::class, 'ability' => AdminAcl::RESOURCE_ALLOCATIONS],
        'subusers' => ['relation' => 'subusers', 'resource' => SubuserResource::class, 'ability' => AdminAcl::RESOURCE_USERS],
        'user' => ['relation' => 'user', 'resource' => UserResource::class, 'ability' => AdminAcl::RESOURCE_USERS],
        'egg' => ['relation' => 'egg', 'resource' => EggResource::class, 'ability' => AdminAcl::RESOURCE_EGGS],
        'location' => ['relation' => 'location', 'resource' => LocationResource::class, 'ability' => AdminAcl::RESOURCE_LOCATIONS],
        'node' => ['relation' => 'node', 'resource' => NodeResource::class, 'ability' => AdminAcl::RESOURCE_NODES],
        'databases' => ['relation' => 'databases', 'resource' => ServerDatabaseResource::class, 'ability' => AdminAcl::RESOURCE_SERVER_DATABASES],
        'transfer' => ['relation' => 'transfer', 'resource' => ServerTransferResource::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
    ];

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

    public function resourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        $server = $this->resource;

        return [
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
                'environment' => App::make(EnvironmentService::class)->handle($server),
                'skip_scripts' => $server->skip_scripts,
            ],
            'relationships' => [],
            $server->getUpdatedAtColumn() => $this->formatTimestamp($server->updated_at),
            $server->getCreatedAtColumn() => $this->formatTimestamp($server->created_at),
            ...$this->extensionFields($server),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'allocations' => $this->includeAllocations(),
            'user' => $this->includeUser(),
            'subusers' => $this->includeSubusers(),
            'egg' => $this->includeEgg(),
            'variables' => $this->includeVariables(),
            'location' => $this->includeLocation(),
            'node' => $this->includeNode(),
            'databases' => $this->includeDatabases(),
            'transfer' => $this->includeTransfer(),
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

    private function includeSubusers(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('subusers')->subusers, SubuserResource::class, 'subuser');
    }

    /**
     * @return ApiResource<User>|NullResource
     */
    private function includeUser(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        return $this->item($this->resource->loadMissing('user')->user, UserResource::class, 'user');
    }

    /**
     * @return ApiResource<Egg>|NullResource
     */
    private function includeEgg(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $egg = $this->resource->loadMissing('egg')->egg ?? throw new UnexpectedValueException('The server does not have an egg.');

        return $this->item($egg, EggResource::class, 'egg');
    }

    private function includeVariables(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->items(App::make(EnvironmentService::class)->variables($this->resource), ServerVariableResource::class, 'server_variable');
    }

    /**
     * @return ApiResource<Location>|NullResource
     */
    private function includeLocation(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_LOCATIONS)) {
            return $this->null();
        }

        $location = $this->resource->loadMissing('location')->location ?? throw new UnexpectedValueException('The server does not have a location.');

        return $this->item($location, LocationResource::class, 'location');
    }

    /**
     * @return ApiResource<Node>|NullResource
     */
    private function includeNode(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->item($this->resource->loadMissing('node')->node, NodeResource::class, 'node');
    }

    private function includeDatabases(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVER_DATABASES)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('databases')->databases, ServerDatabaseResource::class, 'databases');
    }

    /**
     * @return ApiResource<ServerTransfer>|NullResource
     */
    private function includeTransfer(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $transfer = $this->resource->loadMissing('transfer')->transfer;
        if ($transfer === null) {
            return $this->null();
        }

        return $this->item($transfer, ServerTransferResource::class, 'server_transfer');
    }
}
