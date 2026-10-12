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
use Pterodactyl\Contracts\Nodes\CreatesNodes;
use Pterodactyl\Contracts\Nodes\DeletesNodes;
use Pterodactyl\Contracts\Nodes\UpdatesNodes;
use Pterodactyl\Extensions\Scribe\Attributes\ExtensionFieldsParam;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\DeleteNodeRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\GetNodeRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\GetNodesRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\StoreNodeRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\UpdateNodeRequest;
use Pterodactyl\Http\Resources\Api\Admin\NodeResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Models\Node;
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
     */
    #[Endpoint('List nodes', 'Returns a paginated list of Wings nodes.')]
    #[QueryParam('filter[uuid]', 'string', 'Filter nodes by UUID.', required: false, example: '1b19cf3f-2f89-4f88-a81e-321e7fe326bc')]
    #[QueryParam('filter[name]', 'string', 'Filter nodes by name.', required: false, example: 'node-1')]
    #[QueryParam('filter[fqdn]', 'string', 'Filter nodes by FQDN.', required: false, example: 'node.example.com')]
    #[QueryParam('sort', 'string', 'Sort nodes by id, uuid, name, memory, disk, or creation time. Prefix with a hyphen for descending order.', required: false, example: '-created_at')]
    #[QueryParam('page', 'integer', 'Page number to return.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "allocations", "location", "servers", "tags" (the games the node accepts), and "deployment_tags" (its reservations).', required: false, example: 'location', enum: ['allocations', 'location', 'servers', 'allocations,location', 'location,servers', 'tags', 'deployment_tags', 'tags,deployment_tags'])]
    #[ResponseFromResource(NodeResource::class, Node::class, description: 'Nodes returned.', collection: true, factoryStates: ['withLocation'], resourceKey: 'node', paginate: 50, withCount: ['servers'])]
    public function index(GetNodesRequest $request): ApiResourceCollection
    {
        $nodes = QueryBuilder::for(Node::query()->with('location')->withCount('servers'))
            ->allowedFilters(['uuid', 'name', 'fqdn'])
            ->allowedSorts(['id', 'uuid', 'name', 'memory', 'disk', 'created_at'])
            ->paginate($request->perPage());

        return NodeResource::collection($nodes);
    }

    /**
     * Show node.
     */
    #[Endpoint('Get node', 'Returns a single Wings node by ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "allocations", "location", "servers", "tags" (the games the node accepts), and "deployment_tags" (its reservations).', required: false, example: 'location', enum: ['allocations', 'location', 'servers', 'allocations,location', 'location,servers', 'tags', 'deployment_tags', 'tags,deployment_tags'])]
    #[ResponseFromResource(NodeResource::class, Node::class, description: 'Node returned.', factoryStates: ['withLocation'], resourceKey: 'node', include: ['location', 'tags', 'deployment_tags'], withCount: ['servers'])]
    public function show(GetNodeRequest $request, Node $node): NodeResource
    {
        $node->loadCount('servers');

        return NodeResource::make($node)->withExtensionFields();
    }

    /**
     * Create node.
     */
    #[Endpoint('Create node', 'Creates a Wings node. HTTPS nodes must use a valid FQDN rather than a raw IP address.')]
    #[ResponseFromResource(NodeResource::class, Node::class, status: 201, description: 'Node created.', factoryStates: ['withLocation'], resourceKey: 'node', meta: ['resource' => 'https://panel.example.com/api/admin/nodes/1'], withCount: ['servers'])]
    #[ExtensionFieldsParam]
    public function store(StoreNodeRequest $request, CreatesNodes $nodes): JsonResponse
    {
        $node = $nodes->create($request->actionInput());

        Activity::event('admin:node.create')
            ->subject($node)
            ->property('name', $node->name)
            ->log();

        return NodeResource::make($node)
            ->withExtensionFields()
            ->withMeta([
                'resource' => route('api.admin.nodes.view', [
                    'node' => $node->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update node.
     */
    #[Endpoint('Update node', 'Updates a Wings node and optionally rotates its daemon secret.')]
    #[BodyParam('reset_secret', 'boolean', 'Whether to rotate the daemon secret.', required: false, example: false)]
    #[ResponseFromResource(NodeResource::class, Node::class, description: 'Node updated.', factoryStates: ['withLocation'], resourceKey: 'node', withCount: ['servers'])]
    #[ExtensionFieldsParam]
    public function update(UpdateNodeRequest $request, UpdatesNodes $nodes, Node $node): NodeResource
    {
        $node = $nodes->update(
            $node,
            $request->actionInput(),
            $request->boolean('reset_secret')
        );

        Activity::event('admin:node.update')
            ->subject($node)
            ->property('name', $node->name)
            ->log();

        return NodeResource::make($node)->withExtensionFields();
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
