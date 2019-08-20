<?php

namespace App\Http\Controllers\Api\Application\Nodes;

use App\Models\Node;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Services\Nodes\NodeUpdateService;
use App\Services\Nodes\NodeCreationService;
use App\Services\Nodes\NodeDeletionService;
use App\Contracts\Repository\NodeRepositoryInterface;
use App\Transformers\Api\Application\NodeTransformer;
use App\Http\Requests\Api\Application\Nodes\GetNodeRequest;
use App\Http\Requests\Api\Application\Nodes\GetNodesRequest;
use App\Http\Requests\Api\Application\Nodes\StoreNodeRequest;
use App\Http\Requests\Api\Application\Nodes\DeleteNodeRequest;
use App\Http\Requests\Api\Application\Nodes\UpdateNodeRequest;
use App\Http\Controllers\Api\Application\ApplicationApiController;

class NodeController extends ApplicationApiController
{
    /**
     * @var \App\Services\Nodes\NodeCreationService
     */
    private $creationService;

    /**
     * @var \App\Services\Nodes\NodeDeletionService
     */
    private $deletionService;

    /**
     * @var \App\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * @var \App\Services\Nodes\NodeUpdateService
     */
    private $updateService;

    /**
     * NodeController constructor.
     *
     * @param \App\Services\Nodes\NodeCreationService           $creationService
     * @param \App\Services\Nodes\NodeDeletionService           $deletionService
     * @param \App\Services\Nodes\NodeUpdateService             $updateService
     * @param \App\Contracts\Repository\NodeRepositoryInterface $repository
     */
    public function __construct(
        NodeCreationService $creationService,
        NodeDeletionService $deletionService,
        NodeUpdateService $updateService,
        NodeRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->updateService = $updateService;
    }

    /**
     * Return all of the nodes currently available on the Panel.
     *
     * @param \App\Http\Requests\Api\Application\Nodes\GetNodesRequest $request
     * @return array
     */
    public function index(GetNodesRequest $request): array
    {
        $nodes = $this->repository->setSearchTerm($request->input('search'))->paginated(50);

        return $this->fractal->collection($nodes)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toArray();
    }

    /**
     * Return data for a single instance of a node.
     *
     * @param \App\Http\Requests\Api\Application\Nodes\GetNodeRequest $request
     * @return array
     */
    public function view(GetNodeRequest $request): array
    {
        return $this->fractal->item($request->getModel(Node::class))
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toArray();
    }

    /**
     * Create a new node on the Panel. Returns the created node and a HTTP/201
     * status response on success.
     *
     * @param \App\Http\Requests\Api\Application\Nodes\StoreNodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    public function store(StoreNodeRequest $request): JsonResponse
    {
        $node = $this->creationService->handle($request->validated());

        return $this->fractal->item($node)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->addMeta([
                'resource' => route('api.application.nodes.view', [
                    'node' => $node->id,
                ]),
            ])
            ->respond(201);
    }

    /**
     * Update an existing node on the Panel.
     *
     * @param \App\Http\Requests\Api\Application\Nodes\UpdateNodeRequest $request
     * @return array
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateNodeRequest $request): array
    {
        $node = $this->updateService->handle(
            $request->getModel(Node::class), $request->validated(), $request->input('reset_secret') === true
        );

        return $this->fractal->item($node)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a given node from the Panel as long as there are no servers
     * currently attached to it.
     *
     * @param \App\Http\Requests\Api\Application\Nodes\DeleteNodeRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\Service\HasActiveServersException
     */
    public function delete(DeleteNodeRequest $request): Response
    {
        $this->deletionService->handle($request->getModel(Node::class));

        return response('', 204);
    }
}
