<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;
use Pterodactyl\Transformers\Api\Client\AllocationTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Services\Allocations\FindAssignableAllocationService;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\GetNetworkRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\NewAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\DeleteAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\UpdateAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\SetPrimaryAllocationRequest;

class NetworkAllocationController extends ClientApiController
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
     * @var \Pterodactyl\Services\Allocations\FindAssignableAllocationService
     */
    private $assignableAllocationService;

    /**
     * NetworkController constructor.
     */
    public function __construct(
        AllocationRepository $repository,
        ServerRepository $serverRepository,
        FindAssignableAllocationService $assignableAllocationService
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
        $this->assignableAllocationService = $assignableAllocationService;
    }

    /**
     * Lists all of the allocations available to a server and wether or
     * not they are currently assigned as the primary for this server.
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
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateAllocationRequest $request, Server $server, Allocation $allocation): array
    {
        $allocation = $this->repository->update($allocation->id, [
            'notes' => $request->input('notes'),
        ]);

        return $this->fractal->item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    /**
     * Set the primary allocation for a server.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function setPrimary(SetPrimaryAllocationRequest $request, Server $server, Allocation $allocation): array
    {
        $this->serverRepository->update($server->id, ['allocation_id' => $allocation->id]);

        return $this->fractal->item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    /**
     * Set the notes for the allocation for a server.
     *s.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function store(NewAllocationRequest $request, Server $server): array
    {
        if ($server->allocations()->count() >= $server->allocation_limit) {
            throw new DisplayException('Cannot assign additional allocations to this server: limit has been reached.');
        }

        $allocation = $this->assignableAllocationService->handle($server);

        return $this->fractal->item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    /**
     * Delete an allocation from a server.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(DeleteAllocationRequest $request, Server $server, Allocation $allocation)
    {
        if ($allocation->id === $server->allocation_id) {
            throw new DisplayException('You cannot delete the primary allocation for this server.');
        }

        Allocation::query()->where('id', $allocation->id)->update([
            'notes' => null,
            'server_id' => null,
        ]);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
