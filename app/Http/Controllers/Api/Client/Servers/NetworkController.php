<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;
use Pterodactyl\Transformers\Api\Client\AllocationTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\GetNetworkRequest;

class NetworkController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\AllocationRepository
     */
    private $repository;

    /**
     * NetworkController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\AllocationRepository $repository
     */
    public function __construct(AllocationRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Lists all of the allocations available to a server and wether or
     * not they are currently assigned as the primary for this server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Network\GetNetworkRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function index(GetNetworkRequest $request, Server $server): array
    {
        $allocations = $this->repository->findWhere([
            ['server_id', '=', $server->id],
        ]);

        return $this->fractal->collection($allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }
}
