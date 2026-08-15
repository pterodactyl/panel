<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\Transfers\GetServerTransferRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\Transfers\StoreServerTransferRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Repositories\Wings\DaemonTransferRepository;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Pterodactyl\Transformers\Api\Application\ServerTransferTransformer;
use Spatie\QueryBuilder\QueryBuilder;

class ServerTransferController extends ApplicationApiController
{
    /**
     * ServerTransferController constructor.
     */
    public function __construct(
        private AllocationRepositoryInterface $allocationRepository,
        private ConnectionInterface $connection,
        private DaemonTransferRepository $repository,
        private NodeJWTService $nodeJWTService,
        private NodeRepository $nodeRepository,
    ) {
        parent::__construct();
    }

    public function index(GetServerTransferRequest $request, Server $server)
    {
        $transfers = QueryBuilder::for(ServerTransfer::query())
            ->where('server_id', $server->id)
            ->allowedFilters(['id', 'server_id', 'successful'])
            ->allowedSorts(['id', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->query('per_page') ?? 50);

        return $this->fractal->collection($transfers)->transformWith($this->getTransformer(ServerTransferTransformer::class))->toArray();
    }

    /**
     * @throws DisplayException
     */
    public function store(StoreServerTransferRequest $request, Server $server)
    {
        $node_id = $request->input('node_id');
        $allocation_id = $request->input('allocation_id', $this->getAvailableIp($node_id));
        $additional_allocations = $request->input('additional_allocations', []);

        $node = $this->nodeRepository->getNodeWithResourceUsage($node_id);
        if(! $node->isViable($server->memory, $server->disk)) {
            throw new DisplayException('Node is not viable for this server');
        }

        $server->validateTransferState();

        $transfer = new ServerTransfer();
        $transfer->server_id = $server->id;
        $transfer->old_node = $server->node_id;
        $transfer->new_node = $node_id;
        $transfer->old_allocation = $server->allocation_id;
        $transfer->new_allocation = $allocation_id;
        $transfer->old_additional_allocations = $server->allocations->where('id', '!=', $server->allocation_id)->pluck('id');
        $transfer->new_additional_allocations = $additional_allocations;

        $this->connection->transaction(function () use ($server, $node_id, $transfer, $allocation_id, $additional_allocations) {
            // Create a new ServerTransfer entry.
            $transfer->save();

            // Add the allocations to the server, so they cannot be automatically assigned while the transfer is in progress.
            $this->assignAllocationsToServer($server, $node_id, $allocation_id, $additional_allocations);

            // Generate a token for the destination node that the source node can use to authenticate with.
            $token = $this->nodeJWTService
                ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
                ->setSubject($server->uuid)
                ->handle($transfer->newNode, $server->uuid, 'sha256');

            // Notify the source node of the pending outgoing transfer.
            $this->repository->setServer($server)->notify($transfer->newNode, $token);

            return $transfer;
        });

        return $this->fractal->item($transfer)->transformWith($this->getTransformer(ServerTransferTransformer::class))->respond(201);
    }

    private function getAvailableIp(int $node_id): int
    {
        $unassigned = $this->allocationRepository->getUnassignedAllocationIds($node_id);

        if(empty($unassigned)) {
            throw new DisplayException('No unassigned IPs found on the node');
        }

        return $unassigned[0];
    }

    private function assignAllocationsToServer(Server $server, int $node_id, int $allocation_id, array $additional_allocations): void
    {
        $allocations = $additional_allocations;
        $allocations[] = $allocation_id;

        $unassigned = $this->allocationRepository->getUnassignedAllocationIds($node_id);

        $updateIds = [];
        foreach ($allocations as $allocation) {
            if (! in_array($allocation, $unassigned)) {
                continue;
            }

            $updateIds[] = $allocation;
        }

        if (! empty($updateIds)) {
            $this->allocationRepository->updateWhereIn('id', $updateIds, ['server_id' => $server->id]);
        }
    }
}
