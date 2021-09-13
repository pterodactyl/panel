<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Pterodactyl\Transformers\Api\Application\AllocationTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Allocations\GetAllocationsRequest;
use Pterodactyl\Http\Requests\Api\Application\Allocations\StoreAllocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Allocations\DeleteAllocationRequest;

class AllocationController extends ApplicationApiController
{
    private AssignmentService $assignmentService;
    private AllocationDeletionService $deletionService;

    /**
     * AllocationController constructor.
     */
    public function __construct(
        AssignmentService $assignmentService,
        AllocationDeletionService $deletionService
    ) {
        parent::__construct();

        $this->assignmentService = $assignmentService;
        $this->deletionService = $deletionService;
    }

    /**
     * Return all of the allocations that exist for a given node.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetAllocationsRequest $request, Node $node): array
    {
        $perPage = $request->query('per_page', 10);
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $allocations = QueryBuilder::for(Allocation::query()->where('node_id', '=', $node->id))
            ->allowedFilters(['id', 'ip', 'port', 'alias', 'server_id'])
            ->allowedSorts(['id', 'ip', 'port', 'server_id'])
            ->paginate($perPage);

        return $this->fractal->collection($allocations)
            ->transformWith(AllocationTransformer::class)
            ->toArray();
    }

    /**
     * Store new allocations for a given node.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    public function store(StoreAllocationRequest $request, Node $node): Response
    {
        $this->assignmentService->handle($node, $request->validated());

        return $this->returnNoContent();
    }

    /**
     * Delete a specific allocation from the Panel.
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function delete(DeleteAllocationRequest $request, Node $node, Allocation $allocation): Response
    {
        $this->deletionService->handle($allocation);

        return $this->returnNoContent();
    }
}
