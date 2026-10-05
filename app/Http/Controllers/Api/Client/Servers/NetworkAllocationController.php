<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Allocations\AssignsAllocations;
use Pterodactyl\Contracts\Allocations\SetsPrimaryAllocations;
use Pterodactyl\Contracts\Allocations\UnassignsAllocations;
use Pterodactyl\Contracts\Allocations\UpdatesAllocationNotes;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\DeleteAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\GetNetworkRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\NewAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\SetPrimaryAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\UpdateAllocationRequest;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Transformers\Api\Client\AllocationTransformer;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Network', 'View and manage network allocations for an accessible server.')]
class NetworkAllocationController extends ClientApiController
{
    private const array LIMIT_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'Cannot assign additional allocations to this server: limit has been reached.',
            ],
        ],
    ];

    private const array DELETE_PRIMARY_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'You cannot delete the primary allocation for this server.',
            ],
        ],
    ];

    /**
     * Lists all the allocations available to a server and whether
     * they are currently assigned as the primary for this server.
     *
     * @return ApiPayload
     */
    #[Endpoint('List server allocations', 'Returns all network allocations assigned to the server.')]
    #[ResponseFromTransformer(AllocationTransformer::class, Allocation::class, description: 'Server allocations returned.', collection: true, factoryStates: ['withServer'], resourceKey: 'allocation')]
    public function index(GetNetworkRequest $request, Server $server): array
    {
        return Fractal::collection($server->allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * @return ApiPayload
     */
    #[Endpoint('Update allocation notes', 'Updates notes attached to a server allocation.')]
    #[ResponseFromTransformer(AllocationTransformer::class, Allocation::class, description: 'Allocation notes updated.', factoryStates: ['withServer'], resourceKey: 'allocation')]
    public function update(UpdateAllocationRequest $request, UpdatesAllocationNotes $notes, Server $server, Allocation $allocation): array
    {
        $original = $allocation->notes;

        $allocation = $notes->update($allocation, JsonValueGuard::nullableString($request->validated('notes')));

        if ($original !== $allocation->notes) {
            Activity::event('server:allocation.notes')
                ->subject($allocation)
                ->property(['allocation' => $allocation->toString(), 'old' => $original, 'new' => $allocation->notes])
                ->log();
        }

        return Fractal::item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * Set the primary allocation for a server.
     *
     * @return ApiPayload
     */
    #[Endpoint('Set primary allocation', 'Sets the primary network allocation for the server.')]
    #[ResponseFromTransformer(AllocationTransformer::class, Allocation::class, description: 'Primary allocation updated.', factoryStates: ['withServer'], resourceKey: 'allocation')]
    public function setPrimary(SetPrimaryAllocationRequest $request, SetsPrimaryAllocations $allocations, Server $server, Allocation $allocation): array
    {
        $allocation = $allocations->setPrimary($server, $allocation);

        Activity::event('server:allocation.primary')
            ->subject($allocation)
            ->property('allocation', $allocation->toString())
            ->log();

        return Fractal::item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * @return ApiPayload
     *
     * @throws DisplayException
     */
    #[Endpoint('Create server allocation', 'Assigns an available allocation to the server.')]
    #[ResponseFromTransformer(AllocationTransformer::class, Allocation::class, description: 'Allocation assigned.', factoryStates: ['withServer'], resourceKey: 'allocation')]
    #[ScribeResponse(self::LIMIT_ERROR, status: 400, description: 'The server has reached its configured allocation limit or no allocation is available.')]
    public function store(NewAllocationRequest $request, AssignsAllocations $allocations, Server $server): array
    {
        $allocation = $allocations->assign($server);

        Activity::event('server:allocation.create')->subject($allocation)->property('allocation', $allocation->toString())->log();

        return Fractal::item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * Delete an allocation from a server.
     *
     * @throws DisplayException
     */
    #[Endpoint('Delete server allocation', 'Removes a non-primary allocation from the server and returns it to the available pool.')]
    #[ScribeResponse(status: 204, description: 'Allocation removed.')]
    #[ScribeResponse(self::DELETE_PRIMARY_ERROR, status: 400, description: 'The allocation cannot be removed because it is primary or the server has no allocation limit.')]
    public function delete(DeleteAllocationRequest $request, UnassignsAllocations $allocations, Server $server, Allocation $allocation): JsonResponse
    {
        $allocation = $allocations->unassign($server, $allocation);

        Activity::event('server:allocation.delete')
            ->subject($allocation)
            ->property('allocation', $allocation->toString())
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
