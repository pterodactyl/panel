<?php

namespace Pterodactyl\Http\Controllers\API\Admin\Nodes;

use Spatie\Fractal\Fractal;
use Pterodactyl\Models\Node;
use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Http\Controllers\Controller;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Transformers\Api\Admin\AllocationTransformer;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Http\Requests\API\Admin\Allocations\GetAllocationsRequest;
use Pterodactyl\Http\Requests\API\Admin\Allocations\DeleteAllocationRequest;

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
     * @param \Pterodactyl\Http\Requests\API\Admin\Allocations\GetAllocationsRequest $request
     * @param \Pterodactyl\Models\Node                                               $node
     * @return array
     */
    public function index(GetAllocationsRequest $request, Node $node): array
    {
        $allocations = $this->repository->getPaginatedAllocationsForNode($node->id, 100);

        return $this->fractal->collection($allocations)
            ->transformWith((new AllocationTransformer)->setKey($request->key()))
            ->withResourceName('allocation')
            ->paginateWith(new IlluminatePaginatorAdapter($allocations))
            ->toArray();
    }

    /**
     * Delete a specific allocation from the Panel.
     *
     * @param \Pterodactyl\Http\Requests\API\Admin\Allocations\DeleteAllocationRequest $request
     * @param \Pterodactyl\Models\Node                                                 $node
     * @param \Pterodactyl\Models\Allocation                                           $allocation
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function delete(DeleteAllocationRequest $request, Node $node, Allocation $allocation): Response
    {
        $this->deletionService->handle($allocation);

        return response('', 204);
    }
}
