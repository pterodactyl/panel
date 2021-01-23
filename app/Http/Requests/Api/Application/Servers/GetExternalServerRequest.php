<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetExternalServerRequest extends ApplicationApiRequest
{
    /**
     * @var \Pterodactyl\Models\Server
     */
    private $serverModel;

    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_SERVERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;

    /**
     * Determine if the requested external user exists.
     */
    public function resourceExists(): bool
    {
        $repository = $this->container->make(ServerRepositoryInterface::class);

        try {
            $this->serverModel = $repository->findFirstWhere([
                ['external_id', '=', $this->route()->parameter('external_id')],
            ]);
        } catch (RecordNotFoundException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Return the server model for the requested external server.
     */
    public function getServerModel(): Server
    {
        return $this->serverModel;
    }
}
