<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Services\Servers\EnvironmentService;

class ServerTransformer extends BaseTransformer
{
    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService
     */
    private $environmentService;

    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = [
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
     *
     * @param \Pterodactyl\Services\Servers\EnvironmentService $environmentService
     */
    public function handle(EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed server array.
     *
     * @param \Pterodactyl\Models\Server $model
     *
     * @return array
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

            'is_suspended' => $model->suspended,
            'is_installing' => $model->installed !== 1,
            'is_transferring' => ! is_null($model->transfer),

            'user' => $model->owner_id,
            'node' => $model->node_id,
            'allocation' => $model->allocation_id,
            'nest' => $model->nest_id,
            'egg' => $model->egg_id,

            'limits' => [
                'memory' => $model->memory,
                'swap' => $model->swap,
                'disk' => $model->disk,
                'io' => $model->io,
                'cpu' => $model->cpu,
                'threads' => $model->threads,
            ],

            'feature_limits' => [
                'databases' => $model->database_limit,
                'allocations' => $model->allocation_limit,
                'backups' => $model->backup_limit,
            ],

            'container' => [
                'startup_command' => $model->startup,
                'image' => $model->image,
                'installed' => (int) $model->installed === 1,
                'environment' => $this->environmentService->handle($model),
            ],

            $model->getUpdatedAtColumn() => $this->formatTimestamp($model->updated_at),
            $model->getCreatedAtColumn() => $this->formatTimestamp($model->created_at),
        ];
    }

    /**
     * Return a generic array of allocations for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeAllocations(Server $server)
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
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeSubusers(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        $server->loadMissing('subusers');

        return $this->collection($server->getRelation('subusers'), $this->makeTransformer(SubuserTransformer::class), 'subuser');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeUser(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        $server->loadMissing('user');

        return $this->item($server->getRelation('user'), $this->makeTransformer(UserTransformer::class), 'user');
    }

    /**
     * Return a generic array with nest information for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeNest(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NESTS)) {
            return $this->null();
        }

        $server->loadMissing('nest');

        return $this->item($server->getRelation('nest'), $this->makeTransformer(NestTransformer::class), 'nest');
    }

    /**
     * Return a generic array with egg information for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeEgg(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $server->loadMissing('egg');

        return $this->item($server->getRelation('egg'), $this->makeTransformer(EggTransformer::class), 'egg');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeVariables(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $server->loadMissing('variables');

        return $this->collection($server->getRelation('variables'), $this->makeTransformer(ServerVariableTransformer::class), 'server_variable');
    }

    /**
     * Return a generic array with location information for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeLocation(Server $server)
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
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeNode(Server $server)
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
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeDatabases(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVER_DATABASES)) {
            return $this->null();
        }

        $server->loadMissing('databases');

        return $this->collection($server->getRelation('databases'), $this->makeTransformer(ServerDatabaseTransformer::class), 'databases');
    }
}
