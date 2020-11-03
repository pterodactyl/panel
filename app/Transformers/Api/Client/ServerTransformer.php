<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Permission;
use Illuminate\Container\Container;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Servers\StartupCommandService;

class ServerTransformer extends BaseClientTransformer
{
    /**
     * @var string[]
     */
    protected $defaultIncludes = ['allocations', 'variables'];

    /**
     * @var array
     */
    protected $availableIncludes = ['egg', 'subusers'];

    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    /**
     * Transform a server model into a representation that can be returned
     * to a client.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function transform(Server $server): array
    {
        /** @var \Pterodactyl\Services\Servers\StartupCommandService $service */
        $service = Container::getInstance()->make(StartupCommandService::class);

        return [
            'server_owner' => $this->getKey()->user_id === $server->owner_id,
            'identifier' => $server->uuidShort,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'node' => $server->node->name,
            'sftp_details' => [
                'ip' => $server->node->fqdn,
                'port' => $server->node->daemonSFTP,
            ],
            'description' => $server->description,
            'limits' => [
                'memory' => $server->memory,
                'swap' => $server->swap,
                'disk' => $server->disk,
                'io' => $server->io,
                'cpu' => $server->cpu,
            ],
            'invocation' => $service->handle($server, ! $this->getUser()->can(Permission::ACTION_STARTUP_READ, $server)),
            'egg_features' => $server->egg->inherit_features,
            'feature_limits' => [
                'databases' => $server->database_limit,
                'allocations' => $server->allocation_limit,
                'backups' => $server->backup_limit,
            ],
            'is_suspended' => $server->suspended,
            'is_installing' => $server->installed !== 1,
        ];
    }

    /**
     * Returns the allocations associated with this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeAllocations(Server $server)
    {
        if (! $this->getUser()->can(Permission::ACTION_ALLOCATION_READ, $server)) {
            return $this->null();
        }

        return $this->collection(
            $server->allocations,
            $this->makeTransformer(AllocationTransformer::class),
            Allocation::RESOURCE_NAME
        );
    }

    /**
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeVariables(Server $server)
    {
        if (! $this->getUser()->can(Permission::ACTION_STARTUP_READ, $server)) {
            return $this->null();
        }

        return $this->collection(
            $server->variables->where('user_viewable', true),
            $this->makeTransformer(EggVariableTransformer::class),
            EggVariable::RESOURCE_NAME
        );
    }

    /**
     * Returns the egg associated with this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Item
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeEgg(Server $server)
    {
        return $this->item($server->egg, $this->makeTransformer(EggTransformer::class), Egg::RESOURCE_NAME);
    }

    /**
     * Returns the subusers associated with this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeSubusers(Server $server)
    {
        if (! $this->getUser()->can(Permission::ACTION_USER_READ, $server)) {
            return $this->null();
        }

        return $this->collection($server->subusers, $this->makeTransformer(SubuserTransformer::class), Subuser::RESOURCE_NAME);
    }
}
