<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Spatie\Fractal\Fractal;
use Pterodactyl\Models\Node;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Nodes\NodeUpdateService;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Services\Nodes\NodeDeletionService;
use Pterodactyl\Transformers\Api\Admin\NodeTransformer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodeRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodesRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\StoreNodeRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\DeleteNodeRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\UpdateNodeRequest;

class NodeController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Nodes\NodeCreationService
     */
    private $creationService;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeDeletionService
     */
    private $deletionService;

    /**
     * @var \Spatie\Fractal\Fractal
     */
    private $fractal;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeUpdateService
     */
    private $updateService;

    /**
     * NodeController constructor.
     *
     * @param \Spatie\Fractal\Fractal                                   $fractal
     * @param \Pterodactyl\Services\Nodes\NodeCreationService           $creationService
     * @param \Pterodactyl\Services\Nodes\NodeDeletionService           $deletionService
     * @param \Pterodactyl\Services\Nodes\NodeUpdateService             $updateService
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $repository
     */
    public function __construct(
        Fractal $fractal,
        NodeCreationService $creationService,
        NodeDeletionService $deletionService,
        NodeUpdateService $updateService,
        NodeRepositoryInterface $repository
    ) {
        $this->fractal = $fractal;
        $this->repository = $repository;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->updateService = $updateService;
    }

    /**
     * Return all of the nodes currently available on the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodesRequest $request
     * @return array
     */
    public function index(GetNodesRequest $request): array
    {
        $nodes = $this->repository->paginated(100);

        return $this->fractal->collection($nodes)
            ->transformWith((new NodeTransformer)->setKey($request->key()))
            ->withResourceName('node')
            ->paginateWith(new IlluminatePaginatorAdapter($nodes))
            ->toArray();
    }

    /**
     * Return data for a single instance of a node.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodeRequest $request
     * @param \Pterodactyl\Models\Node                                        $node
     * @return array
     */
    public function view(GetNodeRequest $request, Node $node): array
    {
        return $this->fractal->item($node)
            ->transformWith((new NodeTransformer)->setKey($request->key()))
            ->withResourceName('node')
            ->toArray();
    }

    /**
     * Create a new node on the Panel. Returns the created node and a HTTP/201
     * status response on success.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\StoreNodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreNodeRequest $request): JsonResponse
    {
        $node = $this->creationService->handle($request->validated());

        return $this->fractal->item($node)
            ->transformWith((new NodeTransformer)->setKey($request->key()))
            ->withResourceName('node')
            ->addMeta([
                'link' => route('api.admin.node.view', ['node' => $node->id]),
            ])
            ->respond(201);
    }

    /**
     * Update an existing node on the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\UpdateNodeRequest $request
     * @param \Pterodactyl\Models\Node                                           $node
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateNodeRequest $request, Node $node): array
    {
        $node = $this->updateService->returnUpdatedModel()->handle($node, $request->validated());

        return $this->fractal->item($node)
            ->transformWith((new NodeTransformer)->setKey($request->key()))
            ->withResourceName('node')
            ->toArray();
    }

    /**
     * Deletes a given node from the Panel as long as there are no servers
     * currently attached to it.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\DeleteNodeRequest $request
     * @param \Pterodactyl\Models\Node                                           $node
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function delete(DeleteNodeRequest $request, Node $node): Response
    {
        $this->deletionService->handle($node);

        return response('', 204);
    }
}
