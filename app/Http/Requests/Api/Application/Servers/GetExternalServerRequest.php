<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetExternalServerRequest extends ApplicationApiRequest
{
    private Server $serverModel;
    protected string $resource = AdminAcl::RESOURCE_SERVERS;
    protected int $permission = AdminAcl::READ;

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

    public function getServerModel(): Server
    {
        return $this->serverModel;
    }
}
