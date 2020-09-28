<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;
use Pterodactyl\Transformers\Api\Client\AllocationTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\GetNetworkRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\DeleteAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\UpdateAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\NewAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\SetPrimaryAllocationRequest;
use Pterodactyl\Services\Allocations\AssignmentService;

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
     * @var \Pterodactyl\Services\Allocations\AssignmentService
     */
    protected $assignmentService;

    /**
     * NetworkController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\AllocationRepository $repository
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $serverRepository
     * @param \Pterodactyl\Services\Allocations\AssignmentService $assignmentService
     * @param \Illuminate\Contracts\Config\Repository $config
     */

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    public function __construct(
        AllocationRepository $repository,
        ServerRepository $serverRepository,
        AssignmentService $assignmentService,
        Repository $config

    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
        $this->assignmentService = $assignmentService;
        $this->config = $config;
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
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Network\UpdateAllocationRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return array
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
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Network\SetPrimaryAllocationRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return array
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
     *s
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Network\NewAllocationRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function addNew(NewAllocationRequest $request, Server $server): array
    {
        Log::info('addNew()');
        $topRange =  config('pterodactyl.allocation.start');
        $bottomRange = config('pterodactyl.allocation.stop');
        Log::error($bottomRange);
        Log::error($topRange);

        if($server->allocation_limit <= $server->allocations->count()) {
            Log::error('You have created the maximum number of allocations!');
            throw new DisplayException(
                'You have created the maximum number of allocations!'
            );
        }

        $allocation = $server->node->allocations()->where('ip',$server->allocation->ip)->whereNull('server_id')->first();

        if(!$allocation) {
            if($server->node->allocations()->where('ip',$server->allocation->ip)->where([['port', '>=', $bottomRange ], ['port', '<=', $topRange],])->count() >= $topRange-$bottomRange || config('pterodactyl.allocation.enabled', 0)) {
                Log::error('No allocations available!');
                throw new DisplayException(
                    'No more allocations available!'
                );
            }
            Log::info('Creating new allocation...');
            $allPorts = $server->node->allocations()->select(['port'])->where('ip',$server->allocation->ip)->get()->pluck('port')->toArray();

            do {
                $port = rand($bottomRange, $topRange);
                Log::info('Picking port....');
                // TODO ADD ITERATOR THAT TIMES OUT AFTER SEARCHING FOR SO MUCH TIME?
            } while(array_search($port, $allPorts));

            $this->assignmentService->handle($server->node,[
                'allocation_ip'=>$server->allocation->ip,
                'allocation_ports'=>[$port],
                'server_id'=>$server->id
            ]);

            $allocation = $server->node->allocations()->where('ip',$server->allocation->ip)->where('port', $port)->first();

        }

        $allocation->update(['server_id' => $server->id]);

        return $this->fractal->item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    /**
     * Delete an allocation from a server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Network\DeleteAllocationRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(DeleteAllocationRequest $request, Server $server, Allocation $allocation)
    {
        if ($allocation->id === $server->allocation_id) {
            throw new DisplayException(
                'Cannot delete the primary allocation for a server.'
            );
        }

        $this->repository->update($allocation->id, ['server_id' => null, 'notes' => null]);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
