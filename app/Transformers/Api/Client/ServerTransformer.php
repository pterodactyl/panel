<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Allocation;

class ServerTransformer extends BaseClientTransformer
{
    /**
     * @var string[]
     */
    protected $defaultIncludes = ['allocations'];

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
            'feature_limits' => [
                'databases' => $server->database_limit,
                'allocations' => $server->allocation_limit,
                'backups' => $server->backup_limit,
            ],
            'is_suspended' => $server->suspended !== 0,
            'is_installing' => $server->installed !== 1,
        ];
    }

    /**
     * Returns the allocations associated with this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \League\Fractal\Resource\Collection
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeAllocations(Server $server)
    {
        return $this->collection(
            $server->allocations,
            $this->makeTransformer(AllocationTransformer::class),
            Allocation::RESOURCE_NAME
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
     * @return \League\Fractal\Resource\Collection
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeSubusers(Server $server)
    {
        return $this->collection($server->subusers, $this->makeTransformer(SubuserTransformer::class), Subuser::RESOURCE_NAME);
    }
}
