<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;
use Pterodactyl\Transformers\Api\Client\AllocationTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\GetNetworkRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\SetPrimaryAllocationRequest;

class NetworkController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\AllocationRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $serverRepository;

    /**
     * NetworkController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\AllocationRepository $repository
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $serverRepository
     */
    public function __construct(
        AllocationRepository $repository,
        ServerRepository $serverRepository
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
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
        return $this->fractal->collection($server->allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    /**
     * Set the primary allocation for a server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Network\SetPrimaryAllocationRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function storePrimary(SetPrimaryAllocationRequest $request, Server $server): array
    {
        try {
            /** @var \Pterodactyl\Models\Allocation $allocation */
            $allocation = $this->repository->findFirstWhere([
                'server_id' => $server->id,
                'ip' => $request->input('ip'),
                'port' => $request->input('port'),
            ]);
        } catch (ModelNotFoundException $exception) {
            throw new DisplayException(
                'The IP and port you selected are not available for this server.'
            );
        }

        $this->serverRepository->update($server->id, ['allocation_id' => $allocation->id]);

        return $this->fractal->item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }
}
