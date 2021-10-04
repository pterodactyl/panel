<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Pterodactyl\Transformers\Api\Transformer;
use Pterodactyl\Services\Servers\StartupCommandService;

class ServerTransformer extends Transformer
{
    /**
     * @var string[]
     */
    protected $defaultIncludes = ['allocations', 'variables'];

    /**
     * @var array
     */
    protected $availableIncludes = ['egg', 'subusers'];

    protected StartupCommandService $service;

    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    public function handle(StartupCommandService $service)
    {
        $this->service = $service;
    }

    public function transform(Server $server): array
    {
        return [
            'server_owner' => $this->user()->id === $server->owner_id,
            'identifier' => $server->uuidShort,
            'internal_id' => $server->id,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'node' => $server->node->name,
            'sftp_details' => [
                'ip' => $server->node->fqdn,
                'port' => $server->node->public_port_sftp,
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
            'invocation' => $this->service->handle($server, $this->user()->cannot(Permission::ACTION_STARTUP_READ, $server)),
            'docker_image' => $server->image,
            'egg_features' => $server->egg->inherit_features,
            'feature_limits' => [
                'databases' => $server->database_limit,
                'allocations' => $server->allocation_limit,
                'backups' => $server->backup_limit,
            ],
            'status' => $server->status,
            'is_transferring' => !is_null($server->transfer),
        ];
    }

    /**
     * Returns the allocations associated with this server.
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeAllocations(Server $server)
    {
        // While we include this permission, we do need to actually handle it slightly different here
        // for the purpose of keeping things functionally working. If the user doesn't have read permissions
        // for the allocations we'll only return the primary server allocation, and any notes associated
        // with it will be hidden.
        //
        // This allows us to avoid too much permission regression, without also hiding information that
        // is generally needed for the frontend to make sense when browsing or searching results.
        if ($this->user()->cannot(Permission::ACTION_ALLOCATION_READ, $server)) {
            $primary = clone $server->allocation;
            $primary->notes = null;

            return $this->collection([$primary], new AllocationTransformer());
        }

        return $this->collection($server->allocations, new AllocationTransformer());
    }

    /**
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeVariables(Server $server)
    {
        if ($this->user()->cannot(Permission::ACTION_STARTUP_READ, $server)) {
            return $this->null();
        }

        return $this->collection($server->variables->where('user_viewable', true), new EggVariableTransformer());
    }

    /**
     * Returns the egg associated with this server.
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeEgg(Server $server)
    {
        return $this->item($server->egg, new EggTransformer());
    }

    /**
     * Returns the subusers associated with this server.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeSubusers(Server $server)
    {
        if ($this->user()->cannot(Permission::ACTION_USER_READ, $server)) {
            return $this->null();
        }

        return $this->collection($server->subusers, new SubuserTransformer());
    }
}
