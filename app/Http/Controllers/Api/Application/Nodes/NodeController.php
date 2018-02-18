<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Nodes\NodeUpdateService;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Services\Nodes\NodeDeletionService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\NodeTransformer;
use Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodeRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodesRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\StoreNodeRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\DeleteNodeRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\UpdateNodeRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class NodeController extends ApplicationApiController
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
     * @param \Pterodactyl\Services\Nodes\NodeCreationService           $creationService
     * @param \Pterodactyl\Services\Nodes\NodeDeletionService           $deletionService
     * @param \Pterodactyl\Services\Nodes\NodeUpdateService             $updateService
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $repository
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodesRequest $request
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodeRequest $request
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\StoreNodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\UpdateNodeRequest $request
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateNodeRequest $request): array
    {
        $node = $this->updateService->returnUpdatedModel()->handle(
            $request->getModel(Node::class), $request->validated()
        );

        return $this->fractal->item($node)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a given node from the Panel as long as there are no servers
     * currently attached to it.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\DeleteNodeRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function delete(DeleteNodeRequest $request): Response
    {
        $this->deletionService->handle($request->getModel(Node::class));

        return response('', 204);
    }
}
