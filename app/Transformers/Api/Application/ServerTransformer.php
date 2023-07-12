<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Server;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;
use Pterodactyl\Services\Servers\EnvironmentService;

class ServerTransformer extends Transformer
{
    private EnvironmentService $environmentService;

    /**
     * List of resources that can be included.
     */
    protected array $availableIncludes = [
        'allocations',
        'user',
        'subusers',
        'nest',
        'egg',
        'variables',
        'location',
        'node',
        'databases',
        'transfer',
    ];

    /**
     * Perform dependency injection.
     */
    public function handle(EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed server array.
     */
    public function transform(Server $model): array
    {
        return [
            'id' => $model->getKey(),
            'external_id' => $model->external_id,
            'uuid' => $model->uuid,
            'identifier' => $model->uuidShort,
            'name' => $model->name,
            'description' => $model->description,
            'status' => $model->status,
            'limits' => [
                'cpu' => $model->cpu,
                'disk' => $model->disk,
                'io' => $model->io,
                'memory' => $model->memory,
                'oom_killer' => $model->oom_killer,
                'swap' => $model->swap,
                'threads' => $model->threads,
            ],
            'feature_limits' => [
                'allocations' => $model->allocation_limit,
                'backups' => $model->backup_limit,
                'databases' => $model->database_limit,
            ],
            'owner_id' => $model->owner_id,
            'node_id' => $model->node_id,
            'allocation_id' => $model->allocation_id,
            'nest_id' => $model->nest_id,
            'egg_id' => $model->egg_id,
            'container' => [
                'startup' => $model->startup,
                'image' => $model->image,
                'environment' => $this->environmentService->handle($model),
            ],
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }

    /**
     * Return a generic array of allocations for this server.
     */
    public function includeAllocations(Server $server): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_ALLOCATIONS)) {
            return $this->null();
        }

        return $this->collection($server->allocations, new AllocationTransformer());
    }

    /**
     * Return a generic array of data about subusers for this server.
     */
    public function includeSubusers(Server $server): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        return $this->collection($server->subusers, new SubuserTransformer());
    }

    /**
     * Return a generic array of data about subusers for this server.
     */
    public function includeUser(Server $server): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        return $this->item($server->user, new UserTransformer());
    }

    /**
     * Return a generic array with nest information for this server.
     */
    public function includeNest(Server $server): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NESTS)) {
            return $this->null();
        }

        return $this->item($server->nest, new NestTransformer());
    }

    /**
     * Return a generic array with egg information for this server.
     */
    public function includeEgg(Server $server): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        return $this->item($server->egg, new EggTransformer());
    }

    /**
     * Return a generic array of data about subusers for this server.
     */
    public function includeVariables(Server $server): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->collection($server->variables, new ServerVariableTransformer());
    }

    /**
     * Return a generic array with location information for this server.
     */
    public function includeLocation(Server $server): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_LOCATIONS)) {
            return $this->null();
        }

        return $this->item($server->location, new LocationTransformer());
    }

    /**
     * Return a generic array with node information for this server.
     */
    public function includeNode(Server $server): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->item($server->node, new NodeTransformer());
    }

    /**
     * Return a generic array with database information for this server.
     */
    public function includeDatabases(Server $server): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVER_DATABASES)) {
            return $this->null();
        }

        return $this->collection($server->databases, new ServerDatabaseTransformer());
    }
}
