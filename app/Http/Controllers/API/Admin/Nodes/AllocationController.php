<?php

namespace Pterodactyl\Http\Controllers\API\Admin\Nodes;

use Spatie\Fractal\Fractal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Http\Controllers\Controller;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Transformers\Api\Admin\AllocationTransformer;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class AllocationController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Allocations\AllocationDeletionService
     */
    private $deletionService;

    /**
     * @var \Spatie\Fractal\Fractal
     */
    private $fractal;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * AllocationController constructor.
     *
     * @param \Pterodactyl\Services\Allocations\AllocationDeletionService     $deletionService
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface $repository
     * @param \Spatie\Fractal\Fractal                                         $fractal
     */
    public function __construct(AllocationDeletionService $deletionService, AllocationRepositoryInterface $repository, Fractal $fractal)
    {
        $this->deletionService = $deletionService;
        $this->fractal = $fractal;
        $this->repository = $repository;
    }

    /**
     * Return all of the allocations that exist for a given node.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $node
     * @return array
     */
    public function index(Request $request, int $node): array
    {
        $allocations = $this->repository->getPaginatedAllocationsForNode($node, 100);

        return $this->fractal->collection($allocations)
            ->transformWith(new AllocationTransformer($request))
            ->withResourceName('allocation')
            ->paginateWith(new IlluminatePaginatorAdapter($allocations))
            ->toArray();
    }

    /**
     * Delete a specific allocation from the Panel.
     *
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function delete(Request $request, int $node, Allocation $allocation): Response
    {
        $this->deletionService->handle($allocation);

        return response('', 204);
    }
}
