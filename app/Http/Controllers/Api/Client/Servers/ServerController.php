<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\SubuserRepository;
use Pterodactyl\Transformers\Api\Client\ServerTransformer;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;

class ServerController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\SubuserRepository
     */
    private $repository;

    /**
     * ServerController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\SubuserRepository $repository
     */
    public function __construct(SubuserRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Transform an individual server into a response that can be consumed by a
     * client using the API.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function index(GetServerRequest $request, Server $server): array
    {
        try {
            $permissions = $this->repository->findFirstWhere([
                'server_id' => $server->id,
                'user_id' => $request->user()->id,
            ])->permissions;
        } catch (RecordNotFoundException $exception) {
            $permissions = [];
        }

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->addMeta([
                'is_server_owner' => $request->user()->id === $server->owner_id,
                'user_permissions' => $permissions,
            ])
            ->toArray();
    }
}
