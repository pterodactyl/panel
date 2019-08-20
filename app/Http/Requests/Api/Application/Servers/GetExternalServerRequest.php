<?php

namespace App\Http\Requests\Api\Application\Servers;

use App\Models\Server;
use App\Services\Acl\Api\AdminAcl;
use App\Exceptions\Repository\RecordNotFoundException;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Http\Requests\Api\Application\ApplicationApiRequest;

class GetExternalServerRequest extends ApplicationApiRequest
{
    /**
     * @var \App\Models\Server
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
     *
     * @return bool
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
     *
     * @return \App\Models\Server
     */
    public function getServerModel(): Server
    {
        return $this->serverModel;
    }
}
