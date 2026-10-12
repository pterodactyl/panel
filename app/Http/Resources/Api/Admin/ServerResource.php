<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Servers\EnvironmentService;
use UnexpectedValueException;

/**
 * @extends AdminResource<Server>
 */
#[ResponseField('external_id', nullable: true)]
#[ResponseField('description', nullable: true)]
#[ResponseField('feature_limits.databases', 'integer', example: 0, nullable: true)]
#[ResponseField('feature_limits.allocations', 'integer', example: 0, nullable: true)]
#[ResponseField('container.environment', schema: ['type' => 'object', 'additionalProperties' => ['nullable' => true, 'oneOf' => [['type' => 'string'], ['type' => 'number']]], 'example' => ['SERVER_JARFILE' => 'server.jar']])]
class ServerResource extends AdminResource
{
    protected array $eagerLoads = ['egg.variables', 'serverVariables', 'location'];

    protected array $includeRelations = [
        'allocation' => ['relation' => 'allocation', 'resource' => AllocationResource::class],
        'allocations' => ['relation' => 'allocations', 'resource' => AllocationResource::class],
        'user' => ['relation' => 'user', 'resource' => UserResource::class],
        'location' => ['relation' => 'location', 'resource' => LocationResource::class],
        'node' => ['relation' => 'node', 'resource' => NodeResource::class],
        'subusers' => ['relation' => 'subusers', 'resource' => SubuserResource::class],
        'egg' => ['relation' => 'egg', 'resource' => EggResource::class],
        'databases' => ['relation' => 'databases', 'resource' => ServerDatabaseResource::class],
    ];

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
                'skip_scripts' => $server->skip_scripts,
                // Deprecated, use "status".
                'installed' => $server->isInstalled() ? 1 : 0,
                'environment' => App::make(EnvironmentService::class)->forServer($server),
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
            'allocation' => $this->includeAllocation(),
            'allocations' => $this->includeAllocations(),
            'user' => $this->includeUser(),
            'location' => $this->includeLocation(),
            'node' => $this->includeNode(),
            'subusers' => $this->includeSubusers(),
            'egg' => $this->includeEgg(),
            'variables' => $this->includeVariables(),
            'databases' => $this->includeDatabases(),
            default => parent::resolveInclude($include),
        };
    }

    /**
     * @return ApiResource<Allocation>
     */
    private function includeAllocation(): ApiResource
    {
        $allocation = $this->resource->loadMissing('allocation')->allocation ?? throw new UnexpectedValueException('The server does not have a primary allocation.');

        return $this->item($allocation, AllocationResource::class, Allocation::RESOURCE_NAME);
    }

    private function includeAllocations(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('allocations.server')->allocations, AllocationResource::class, 'allocation');
    }

    /**
     * @return ApiResource<User>
     */
    private function includeUser(): ApiResource
    {
        return $this->item($this->resource->loadMissing('user')->user, UserResource::class, 'user');
    }

    /**
     * @return ApiResource<Location>
     */
    private function includeLocation(): ApiResource
    {
        $location = $this->resource->loadMissing('location')->location ?? throw new UnexpectedValueException('The server does not have a location.');

        return $this->item($location, LocationResource::class, 'location');
    }

    /**
     * @return ApiResource<Node>
     */
    private function includeNode(): ApiResource
    {
        return $this->item($this->resource->loadMissing('node')->node, NodeResource::class, 'node');
    }

    private function includeSubusers(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('subusers')->subusers, SubuserResource::class, 'subuser');
    }

    /**
     * @return ApiResource<Egg>
     */
    private function includeEgg(): ApiResource
    {
        $egg = $this->resource->loadMissing('egg')->egg ?? throw new UnexpectedValueException('The server does not have an egg.');

        return $this->item($egg, EggResource::class, 'egg');
    }

    private function includeVariables(): ApiResourceCollection
    {
        return $this->items($this->resource->resolvedVariables(), ServerVariableResource::class, 'server_variable');
    }

    private function includeDatabases(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('databases')->databases, ServerDatabaseResource::class, 'databases');
    }
}
