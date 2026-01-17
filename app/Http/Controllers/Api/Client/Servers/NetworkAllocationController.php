<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Allocation;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
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
     * NetworkAllocationController constructor.
     */
    public function __construct(
        protected readonly ConnectionInterface $connection,
        private FindAssignableAllocationService $assignableAllocationService,
        private ServerRepository $serverRepository,
    ) {
        parent::__construct();
    }

    /**
     * Lists all the allocations available to a server and whether
     * they are currently assigned as the primary for this server.
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
        $original = $allocation->notes;

        $allocation->forceFill(['notes' => $request->input('notes')])->save();

        if ($original !== $allocation->notes) {
            Activity::event('server:allocation.notes')
                ->subject($allocation)
                ->property(['allocation' => $allocation->toString(), 'old' => $original, 'new' => $allocation->notes])
                ->log();
        }

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

        Activity::event('server:allocation.primary')
            ->subject($allocation)
            ->property('allocation', $allocation->toString())
            ->log();

        return $this->fractal->item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    /**
     * Set the notes for the allocation for a server.
     *s.
     *
     * @throws DisplayException
     */
    public function store(NewAllocationRequest $request, Server $server): array
    {
        $allocation = Activity::event('server:allocation.create')->transaction(function ($log) use ($server) {
            if ($server->allocations()->lockForUpdate()->count() >= $server->allocation_limit) {
                throw new DisplayException('Cannot assign additional allocations to this server: limit has been reached.');
            }

            $allocation = $this->assignableAllocationService->handle($server);

            $log->subject($allocation)->property('allocation', $allocation->toString());

            return $allocation;
        });

        return $this->fractal->item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    /**
     * Delete an allocation from a server.
     *
     * @throws DisplayException
     */
    public function delete(DeleteAllocationRequest $request, Server $server, Allocation $allocation): JsonResponse
    {
        // Don't allow the deletion of allocations if the server does not have an
        // allocation limit set.
        if (empty($server->allocation_limit)) {
            throw new DisplayException('You cannot delete allocations for this server: no allocation limit is set.');
        }

        if ($allocation->id === $server->allocation_id) {
            throw new DisplayException('You cannot delete the primary allocation for this server.');
        }

        Allocation::query()->where('id', $allocation->id)->update([
            'notes' => null,
            'server_id' => null,
        ]);

        Activity::event('server:allocation.delete')
            ->subject($allocation)
            ->property('allocation', $allocation->toString())
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
