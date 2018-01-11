<?php

namespace Pterodactyl\Http\Controllers\API\Admin\Nodes;

use Spatie\Fractal\Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Nodes\NodeUpdateService;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Services\Nodes\NodeDeletionService;
use Pterodactyl\Transformers\Api\Admin\NodeTransformer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

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
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        $nodes = $this->repository->paginated(100);

        $fractal = $this->fractal->collection($nodes)
            ->transformWith(new NodeTransformer($request))
            ->withResourceName('node')
            ->paginateWith(new IlluminatePaginatorAdapter($nodes));

        return $fractal->toArray();
    }

    /**
     * Return data for a single instance of a node.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Node $node
     * @return array
     */
    public function view(Request $request, Node $node): array
    {
        $fractal = $this->fractal->item($node)
            ->transformWith(new NodeTransformer($request))
            ->withResourceName('node');

        return $fractal->toArray();
    }

    /**
     * Create a new node on the Panel. Returns the created node and a HTTP/201
     * status response on success.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(NodeFormRequest $request): JsonResponse
    {
        $node = $this->creationService->handle($request->normalize());

        return $this->fractal->item($node)
            ->transformWith(new NodeTransformer($request))
            ->withResourceName('node')
            ->addMeta([
                'link' => route('api.admin.node.view', ['node' => $node->id]),
            ])
            ->respond(201);
    }

    /**
     * Update an existing node on the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest $request
     * @param \Pterodactyl\Models\Node                              $node
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(NodeFormRequest $request, Node $node): array
    {
        $node = $this->updateService->returnUpdatedModel()->handle($node, $request->normalize());

        return $this->fractal->item($node)
            ->transformWith(new NodeTransformer($request))
            ->withResourceName('node')
            ->toArray();
    }

    /**
     * Deletes a given node from the Panel as long as there are no servers
     * currently attached to it.
     *
     * @param \Pterodactyl\Models\Node $node
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function delete(Node $node): Response
    {
        $this->deletionService->handle($node);

        return response('', 201);
    }
}
