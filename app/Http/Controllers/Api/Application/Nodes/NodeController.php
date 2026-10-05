<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Nodes\CreatesNodes;
use Pterodactyl\Contracts\Nodes\DeletesNodes;
use Pterodactyl\Contracts\Nodes\UpdatesNodes;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Nodes\DeleteNodeRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodeRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodesRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\StoreNodeRequest;
use Pterodactyl\Http\Requests\Api\Application\Nodes\UpdateNodeRequest;
use Pterodactyl\Models\Node;
use Pterodactyl\Transformers\Api\Application\NodeTransformer;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Nodes', 'Create, update, retrieve, and delete Wings nodes and their allocations.')]
class NodeController extends ApplicationApiController
{
    private const array ACTIVE_SERVERS_ERROR = [
        'errors' => [
            [
                'code' => 'HasActiveServersException',
                'status' => '400',
                'detail' => 'A node must have no servers linked to it in order to be deleted.',
            ],
        ],
    ];

    private const array CONFIGURATION_NOT_PERSISTED_ERROR = [
        'errors' => [
            [
                'code' => 'ConfigurationNotPersistedException',
                'status' => '400',
                'detail' => 'The daemon configuration has been updated, however there was an error encountered while attempting to automatically update the configuration file on the Daemon. You will need to manually update the configuration file (config.yml) for the daemon to apply these changes.',
            ],
        ],
    ];

    /**
     * Return all the nodes currently available on the Panel.
     *
     * @return ApiPayload
     */
    #[Endpoint('List nodes', 'Returns a paginated list of Wings nodes registered on the panel.')]
    #[QueryParam('per_page', 'integer', 'Number of nodes to return per page, up to 100.', required: false, example: 50)]
    #[QueryParam('filter[uuid]', 'string', 'Filter nodes by UUID.', required: false, example: '3d8dd7f9-07a1-4d65-8db0-8c8921f2f4f7')]
    #[QueryParam('filter[name]', 'string', 'Filter nodes by name.', required: false, example: 'Node 1')]
    #[QueryParam('filter[fqdn]', 'string', 'Filter nodes by fully qualified domain name.', required: false, example: 'node.example.com')]
    #[QueryParam('filter[daemon_token_id]', 'string', 'Filter nodes by daemon token identifier.', required: false, example: 'abcdefghijklmnop')]
    #[QueryParam('sort', 'string', 'Sort nodes by id, uuid, memory, or disk. Prefix with "-" for descending order.', required: false, example: '-id', enum: ['id', '-id', 'uuid', '-uuid', 'memory', '-memory', 'disk', '-disk'])]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "allocations", "location", and "servers" when the key can read those resources.', required: false, example: 'location,allocations', enum: ['allocations', 'location', 'servers', 'location,allocations'])]
    #[ResponseFromTransformer(NodeTransformer::class, Node::class, collection: true, factoryStates: ['withLocation'], resourceKey: 'node', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetNodesRequest $request): array
    {
        $nodes = QueryBuilder::for(Node::query()
            ->withSum('servers as sum_memory', 'memory')
            ->withSum('servers as sum_disk', 'disk'))
            ->allowedFilters(['uuid', 'name', 'fqdn', 'daemon_token_id'])
            ->allowedSorts(['id', 'uuid', 'memory', 'disk'])
            ->paginate($request->perPage());

        return Fractal::collection($nodes)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toResponseArray();
    }

    /**
     * Return data for a single instance of a node.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get node', 'Returns a single Wings node by internal numeric ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "allocations", "location", and "servers" when the key can read those resources.', required: false, example: 'location,allocations', enum: ['allocations', 'location', 'servers', 'location,allocations'])]
    #[ResponseFromTransformer(NodeTransformer::class, Node::class, factoryStates: ['withLocation'], resourceKey: 'node')]
    public function view(GetNodeRequest $request, Node $node): array
    {
        return Fractal::item($node)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create a new node on the Panel. Returns the created node and an HTTP/201
     * status response on success.
     */
    #[Endpoint('Create node', 'Creates a new Wings node and returns the created resource.')]
    #[ResponseFromTransformer(NodeTransformer::class, Node::class, status: 201, description: 'Node created.', factoryStates: ['withLocation'], resourceKey: 'node', meta: ['resource' => 'https://panel.example.test/api/application/nodes/1'])]
    public function store(StoreNodeRequest $request, CreatesNodes $nodes): JsonResponse
    {
        $node = $nodes->create($request->payload());

        return Fractal::item($node)
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
     *
     * @return ApiPayload
     *
     * @throws Throwable
     */
    #[Endpoint('Update node', 'Updates an existing Wings node by internal numeric ID.')]
    #[ResponseFromTransformer(NodeTransformer::class, Node::class, factoryStates: ['withLocation'], resourceKey: 'node')]
    #[ScribeResponse(self::CONFIGURATION_NOT_PERSISTED_ERROR, status: 400, description: 'The panel saved the node changes but could not persist the configuration to Wings.')]
    public function update(UpdateNodeRequest $request, UpdatesNodes $nodes, Node $node): array
    {
        $node = $nodes->update(
            $node,
            $request->payload(),
            $request->boolean('reset_secret')
        );

        return Fractal::item($node)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toResponseArray();
    }

    /**
     * Deletes a given node from the Panel as long as there are no servers
     * currently attached to it.
     *
     * @throws HasActiveServersException
     */
    #[Endpoint('Delete node', 'Deletes a Wings node by internal numeric ID when no servers are attached.')]
    #[ScribeResponse(status: 204, description: 'Node deleted.')]
    #[ScribeResponse(self::ACTIVE_SERVERS_ERROR, status: 400, description: 'The node has active servers attached.')]
    public function delete(DeleteNodeRequest $request, DeletesNodes $nodes, Node $node): JsonResponse
    {
        $nodes->delete($node);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
