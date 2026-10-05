<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Nodes;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Nodes\CreatesNodes;
use Pterodactyl\Contracts\Nodes\DeletesNodes;
use Pterodactyl\Contracts\Nodes\UpdatesNodes;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\DeleteNodeRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\GetNodeRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\GetNodesRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\StoreNodeRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\UpdateNodeRequest;
use Pterodactyl\Models\Node;
use Pterodactyl\Transformers\Api\Admin\NodeTransformer;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Nodes', 'Create, update, retrieve, and delete Wings nodes.')]
class NodeController extends AdminApiController
{
    private const array HAS_SERVERS_ERROR = [
        'errors' => [
            [
                'code' => 'HasActiveServersException',
                'status' => '400',
                'detail' => 'This node has active servers and cannot be deleted.',
            ],
        ],
    ];

    /**
     * List nodes.
     *
     * @return ApiPayload
     */
    #[Endpoint('List nodes', 'Returns a paginated list of Wings nodes.')]
    #[QueryParam('filter[uuid]', 'string', 'Filter nodes by UUID.', required: false, example: '1b19cf3f-2f89-4f88-a81e-321e7fe326bc')]
    #[QueryParam('filter[name]', 'string', 'Filter nodes by name.', required: false, example: 'node-1')]
    #[QueryParam('filter[fqdn]', 'string', 'Filter nodes by FQDN.', required: false, example: 'node.example.com')]
    #[QueryParam('sort', 'string', 'Sort nodes by id, uuid, name, memory, disk, or creation time. Prefix with a hyphen for descending order.', required: false, example: '-created_at')]
    #[QueryParam('page', 'integer', 'Page number to return.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "allocations", "location", and "servers".', required: false, example: 'location', enum: ['allocations', 'location', 'servers', 'allocations,location', 'location,servers'])]
    #[ResponseFromTransformer(NodeTransformer::class, Node::class, description: 'Nodes returned.', collection: true, factoryStates: ['withLocation'], resourceKey: 'node', paginate: [IlluminatePaginatorAdapter::class, 50], withCount: ['servers'])]
    public function index(GetNodesRequest $request): array
    {
        $nodes = QueryBuilder::for(Node::query()->with('location')->withCount('servers'))
            ->allowedFilters(['uuid', 'name', 'fqdn'])
            ->allowedSorts(['id', 'uuid', 'name', 'memory', 'disk', 'created_at'])
            ->paginate($request->perPage());

        return Fractal::collection($nodes)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toResponseArray();
    }

    /**
     * Show node.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get node', 'Returns a single Wings node by ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "allocations", "location", and "servers".', required: false, example: 'location', enum: ['allocations', 'location', 'servers', 'allocations,location', 'location,servers'])]
    #[ResponseFromTransformer(NodeTransformer::class, Node::class, description: 'Node returned.', factoryStates: ['withLocation'], resourceKey: 'node', include: ['location'], withCount: ['servers'])]
    public function show(GetNodeRequest $request, Node $node): array
    {
        $node->loadCount('servers');

        return Fractal::item($node)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create node.
     */
    #[Endpoint('Create node', 'Creates a Wings node. HTTPS nodes must use a valid FQDN rather than a raw IP address.')]
    #[ResponseFromTransformer(NodeTransformer::class, Node::class, status: 201, description: 'Node created.', factoryStates: ['withLocation'], resourceKey: 'node', meta: ['resource' => 'https://panel.example.com/api/admin/nodes/1'], withCount: ['servers'])]
    public function store(StoreNodeRequest $request, CreatesNodes $nodes): JsonResponse
    {
        $node = $nodes->create($request->payload());

        Activity::event('admin:node.create')
            ->subject($node)
            ->property('name', $node->name)
            ->log();

        return Fractal::item($node)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->addMeta([
                'resource' => route('api.admin.nodes.view', [
                    'node' => $node->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update node.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update node', 'Updates a Wings node and optionally rotates its daemon secret.')]
    #[BodyParam('reset_secret', 'boolean', 'Whether to rotate the daemon secret.', required: false, example: false)]
    #[ResponseFromTransformer(NodeTransformer::class, Node::class, description: 'Node updated.', factoryStates: ['withLocation'], resourceKey: 'node', withCount: ['servers'])]
    public function update(UpdateNodeRequest $request, UpdatesNodes $nodes, Node $node): array
    {
        $node = $nodes->update(
            $node,
            $request->payload(),
            $request->boolean('reset_secret')
        );

        Activity::event('admin:node.update')
            ->subject($node)
            ->property('name', $node->name)
            ->log();

        return Fractal::item($node)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toResponseArray();
    }

    /**
     * Delete node.
     */
    #[Endpoint('Delete node', 'Deletes a node when it has no active servers.')]
    #[ScribeResponse(status: 204, description: 'Node deleted.')]
    #[ScribeResponse(self::HAS_SERVERS_ERROR, status: 400, description: 'The node has active servers.')]
    public function destroy(DeleteNodeRequest $request, DeletesNodes $nodes, Node $node): Response
    {
        $nodes->delete($node);

        Activity::event('admin:node.delete')
            ->subject($node)
            ->property('name', $node->name)
            ->log();

        return $this->returnNoContent();
    }
}
