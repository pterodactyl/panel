<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\AllocationTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\GetNetworkRequest;

class NetworkController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * NetworkController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface $repository
     */
    public function __construct(AllocationRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Lists all of the allocations available to a server and wether or
     * not they are currently assigned as the primary for this server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Network\GetNetworkRequest $request
     * @return array
     */
    public function index(GetNetworkRequest $request): array
    {
        $server = $request->getModel(Server::class);

        $allocations = $this->repository->findWhere([
            ['server_id', '=', $server->id],
        ]);

        return $this->fractal->collection($allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }
}
