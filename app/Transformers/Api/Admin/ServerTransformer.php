<?php

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Admin\PackTransformer;
use Pterodactyl\Transformers\Admin\ServerVariableTransformer;

class ServerTransformer extends BaseTransformer
{
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
     * Return a generic transformed server array.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function transform(Server $server): array
    {
        return collect($server->toArray())->only($server->getTableColumns())->toArray();
    }

    /**
     * Return a generic array of allocations for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
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
     */
    public function includePack(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_PACKS)) {
            return $this->null();
        }

        $server->loadMissing('pack');

        return $this->item($server->getRelation('pack'), $this->makeTransformer(PackTransformer::class), 'pack');
    }

    /**
     * Return a generic array with nest information for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
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
     */
    public function includeOption(Server $server)
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
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeVariables(Server $server)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $server->loadMissing('variables');

        return $this->item($server->getRelation('variables'), $this->makeTransformer(ServerVariableTransformer::class), 'server_variable');
    }

    /**
     * Return a generic array with pack information for this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
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
