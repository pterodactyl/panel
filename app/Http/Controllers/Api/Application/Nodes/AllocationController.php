<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\AllocationTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Allocations\GetAllocationsRequest;
use Pterodactyl\Http\Requests\Api\Application\Allocations\StoreAllocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Allocations\DeleteAllocationRequest;

class AllocationController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Allocations\AssignmentService
     */
    private $assignmentService;

    /**
     * @var \Pterodactyl\Services\Allocations\AllocationDeletionService
     */
    private $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * AllocationController constructor.
     *
     * @param \Pterodactyl\Services\Allocations\AssignmentService             $assignmentService
     * @param \Pterodactyl\Services\Allocations\AllocationDeletionService     $deletionService
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface $repository
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Allocations\GetAllocationsRequest $request
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Allocations\StoreAllocationRequest $request
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function store(StoreAllocationRequest $request): array
    {
        $this->assignmentService->handle($request->getModel(Node::class), $request->validated());

        return response('', 204);
    }

    /**
     * Delete a specific allocation from the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Allocations\DeleteAllocationRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function delete(DeleteAllocationRequest $request): Response
    {
        $this->deletionService->handle($request->getModel(Allocation::class));

        return response('', 204);
    }
}
