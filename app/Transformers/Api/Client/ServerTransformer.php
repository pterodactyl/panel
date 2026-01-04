<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use League\Fractal\Resource\Item;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Permission;
use Illuminate\Container\Container;
use Pterodactyl\Models\EggVariable;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Servers\StartupCommandService;

class ServerTransformer extends BaseClientTransformer
{
    protected array $defaultIncludes = ['allocations', 'variables'];

    protected array $availableIncludes = ['egg', 'subusers'];

    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    /**
     * Transform a server model into a representation that can be returned
     * to a client.
     */
    public function transform(Server $server): array
    {
        /** @var StartupCommandService $service */
        $service = Container::getInstance()->make(StartupCommandService::class);

        $user = $this->request->user();

        return [
            'server_owner' => $user->id === $server->owner_id,
            'identifier' => $server->uuidShort,
            'internal_id' => $server->id,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'node' => $server->node->name,
            'is_node_under_maintenance' => $server->node->isUnderMaintenance(),
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
                'threads' => $server->threads,
                'oom_disabled' => $server->oom_disabled,
            ],
            'invocation' => $service->handle($server, !$user->can(Permission::ACTION_STARTUP_READ, $server)),
            'docker_image' => $server->image,
            'egg_features' => $server->egg->inherit_features,
            'feature_limits' => [
                'databases' => $server->database_limit,
                'allocations' => $server->allocation_limit,
                'backups' => $server->backup_limit,
            ],
            'status' => $server->status,
            // This field is deprecated, please use "status".
            'is_suspended' => $server->isSuspended(),
            // This field is deprecated, please use "status".
            'is_installing' => !$server->isInstalled(),
            'is_transferring' => !is_null($server->transfer),
        ];
    }

    /**
     * Returns the allocations associated with this server.
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeAllocations(Server $server): Collection
    {
        $transformer = $this->makeTransformer(AllocationTransformer::class);

        $user = $this->request->user();
        // While we include this permission, we do need to actually handle it slightly different here
        // for the purpose of keeping things functionally working. If the user doesn't have read permissions
        // for the allocations we'll only return the primary server allocation, and any notes associated
        // with it will be hidden.
        //
        // This allows us to avoid too much permission regression, without also hiding information that
        // is generally needed for the frontend to make sense when browsing or searching results.
        if (!$user->can(Permission::ACTION_ALLOCATION_READ, $server)) {
            $primary = clone $server->allocation;
            $primary->notes = null;

            return $this->collection([$primary], $transformer, Allocation::RESOURCE_NAME);
        }

        return $this->collection($server->allocations, $transformer, Allocation::RESOURCE_NAME);
    }

    /**
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeVariables(Server $server): Collection|NullResource
    {
        if (!$this->request->user()->can(Permission::ACTION_STARTUP_READ, $server)) {
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
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeEgg(Server $server): Item
    {
        return $this->item($server->egg, $this->makeTransformer(EggTransformer::class), Egg::RESOURCE_NAME);
    }

    /**
     * Returns the subusers associated with this server.
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeSubusers(Server $server): Collection|NullResource
    {
        if (!$this->request->user()->can(Permission::ACTION_USER_READ, $server)) {
            return $this->null();
        }

        return $this->collection($server->subusers, $this->makeTransformer(SubuserTransformer::class), Subuser::RESOURCE_NAME);
    }
}
