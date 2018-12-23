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
        'pack',
        'nest',
        'egg',
        'variables',
        'location',
        'node',
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
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function transform(Server $server): array
    {
        return [
            'id' => $server->getKey(),
            'external_id' => $server->external_id,
            'uuid' => $server->uuid,
            'identifier' => $server->uuidShort,
            'name' => $server->name,
            'description' => $server->description,
            'suspended' => (bool) $server->suspended,
            'limits' => [
                'memory' => $server->memory,
                'swap' => $server->swap,
                'disk' => $server->disk,
                'io' => $server->io,
                'cpu' => $server->cpu,
            ],
            'feature_limits' => [
                'databases' => $server->database_limit,
                'allocations' => $server->allocation_limit,
            ],
            'user' => $server->owner_id,
            'node' => $server->node_id,
            'allocation' => $server->allocation_id,
            'nest' => $server->nest_id,
            'egg' => $server->egg_id,
            'pack' => $server->pack_id,
            'container' => [
                'startup_command' => $server->startup,
                'image' => $server->image,
                'installed' => (int) $server->installed === 1,
                'environment' => $this->environmentService->handle($server),
            ],
            $server->getUpdatedAtColumn() => $this->formatTimestamp($server->updated_at),
            $server->getCreatedAtColumn() => $this->formatTimestamp($server->created_at),
        ];
    }

    /**
     * Return a generic array of allocations for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
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
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeSubusers(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        $server->loadMissing('subusers');

        return $this->collection($server->getRelation('subusers'), $this->makeTransformer(UserTransformer::class), 'user');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
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
     * Return a generic array with pack information for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includePack(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_PACKS)) {
            return $this->null();
        }

        $server->loadMissing('pack');
        if (is_null($server->getRelation('pack'))) {
            return $this->null();
        }

        return $this->item($server->getRelation('pack'), $this->makeTransformer(PackTransformer::class), 'pack');
    }

    /**
     * Return a generic array with nest information for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
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
     * Return a generic array with service option information for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeOption(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $server->loadMissing('egg');

        return $this->item($server->getRelation('egg'), $this->makeTransformer(EggVariableTransformer::class), 'egg');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
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
     * Return a generic array with pack information for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
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
     * Return a generic array with pack information for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeNode(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        $server->loadMissing('node');

        return $this->item($server->getRelation('node'), $this->makeTransformer(NodeTransformer::class), 'node');
    }
}
