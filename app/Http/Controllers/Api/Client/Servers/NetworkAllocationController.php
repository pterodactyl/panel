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
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\DeleteAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\GetNetworkRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\NewAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\SetPrimaryAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\UpdateAllocationRequest;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Client\AllocationResource;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;

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
     */
    #[Endpoint('List server allocations', 'Returns all network allocations assigned to the server.')]
    #[ResponseFromResource(AllocationResource::class, Allocation::class, description: 'Server allocations returned.', collection: true, factoryStates: ['withServer'], resourceKey: 'allocation')]
    public function index(GetNetworkRequest $request, Server $server): ApiResourceCollection
    {
        return AllocationResource::collection($server->allocations);
    }

    #[Endpoint('Update allocation notes', 'Updates notes attached to a server allocation.')]
    #[ResponseFromResource(AllocationResource::class, Allocation::class, description: 'Allocation notes updated.', factoryStates: ['withServer'], resourceKey: 'allocation')]
    public function update(UpdateAllocationRequest $request, UpdatesAllocationNotes $notes, Server $server, Allocation $allocation): AllocationResource
    {
        $allocation = $notes->update($allocation, $request->filled('notes') ? $request->string('notes')->toString() : null);

        return AllocationResource::make($allocation);
    }

    /**
     * Set the primary allocation for a server.
     */
    #[Endpoint('Set primary allocation', 'Sets the primary network allocation for the server.')]
    #[ResponseFromResource(AllocationResource::class, Allocation::class, description: 'Primary allocation updated.', factoryStates: ['withServer'], resourceKey: 'allocation')]
    public function setPrimary(SetPrimaryAllocationRequest $request, SetsPrimaryAllocations $allocations, Server $server, Allocation $allocation): AllocationResource
    {
        $allocation = $allocations->setPrimary($server, $allocation);

        return AllocationResource::make($allocation);
    }

    /**
     * @throws DisplayException
     */
    #[Endpoint('Create server allocation', 'Assigns an available allocation to the server.')]
    #[ResponseFromResource(AllocationResource::class, Allocation::class, description: 'Allocation assigned.', factoryStates: ['withServer'], resourceKey: 'allocation')]
    #[ScribeResponse(self::LIMIT_ERROR, status: 400, description: 'The server has reached its configured allocation limit or no allocation is available.')]
    public function store(NewAllocationRequest $request, AssignsAllocations $allocations, Server $server): AllocationResource
    {
        $allocation = $allocations->assign($server);

        return AllocationResource::make($allocation);
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
        $allocations->unassign($server, $allocation);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
