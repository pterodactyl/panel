<?php

namespace App\Http\Controllers\Api\Application\Nodes;

use App\Models\Node;
use Illuminate\Http\Response;
use App\Models\Allocation;
use App\Services\Allocations\AssignmentService;
use App\Services\Allocations\AllocationDeletionService;
use App\Contracts\Repository\AllocationRepositoryInterface;
use App\Transformers\Api\Application\AllocationTransformer;
use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Http\Requests\Api\Application\Allocations\GetAllocationsRequest;
use App\Http\Requests\Api\Application\Allocations\StoreAllocationRequest;
use App\Http\Requests\Api\Application\Allocations\DeleteAllocationRequest;

class AllocationController extends ApplicationApiController
{
    /**
     * @var \App\Services\Allocations\AssignmentService
     */
    private $assignmentService;

    /**
     * @var \App\Services\Allocations\AllocationDeletionService
     */
    private $deletionService;

    /**
     * @var \App\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * AllocationController constructor.
     *
     * @param \App\Services\Allocations\AssignmentService             $assignmentService
     * @param \App\Services\Allocations\AllocationDeletionService     $deletionService
     * @param \App\Contracts\Repository\AllocationRepositoryInterface $repository
     */
    public function __construct(
        AssignmentService $assignmentService,
        AllocationDeletionService $deletionService,
        AllocationRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->assignmentService = $assignmentService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
    }

    /**
     * Return all of the allocations that exist for a given node.
     *
     * @param \App\Http\Requests\Api\Application\Allocations\GetAllocationsRequest $request
     * @return array
     */
    public function index(GetAllocationsRequest $request): array
    {
        $allocations = $this->repository->getPaginatedAllocationsForNode(
            $request->getModel(Node::class)->id, 50
        );

        return $this->fractal->collection($allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    /**
     * Store new allocations for a given node.
     *
     * @param \App\Http\Requests\Api\Application\Allocations\StoreAllocationRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \App\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \App\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \App\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    public function store(StoreAllocationRequest $request): Response
    {
        $this->assignmentService->handle($request->getModel(Node::class), $request->validated());

        return response('', 204);
    }

    /**
     * Delete a specific allocation from the Panel.
     *
     * @param \App\Http\Requests\Api\Application\Allocations\DeleteAllocationRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function delete(DeleteAllocationRequest $request): Response
    {
        $this->deletionService->handle($request->getModel(Allocation::class));

        return response('', 204);
    }
}
