<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Nodes\GetDeployableNodesRequest;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Deployment\FindViableNodesService;
use Pterodactyl\Transformers\Api\Application\NodeTransformer;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Nodes', 'Create, update, retrieve, and delete Wings nodes and their allocations.')]
class NodeDeploymentController extends ApplicationApiController
{
    private const array NO_VIABLE_NODE_ERROR = [
        'errors' => [
            [
                'code' => 'NoViableNodeException',
                'status' => '400',
                'detail' => 'No nodes satisfying the requirements specified for automatic deployment could be found.',
            ],
        ],
    ];

    /**
     * Finds any nodes that are available using the given deployment criteria. This works
     * similarly to the server creation process, but allows you to pass the deployment object
     * to this endpoint and get back a list of all Nodes satisfying the requirements.
     *
     *
     * @return ApiPayload
     *
     * @throws NoViableNodeException
     */
    #[Endpoint('List deployable nodes', 'Returns nodes that can satisfy the requested memory, disk, and optional location deployment constraints.')]
    #[QueryParam('memory', 'integer', 'Memory required for the deployment, in megabytes.', required: true, example: 1024)]
    #[QueryParam('disk', 'integer', 'Disk required for the deployment, in megabytes.', required: true, example: 10240)]
    #[QueryParam('location_ids', 'integer[]', 'Restrict results to these location IDs.', required: false, example: [1, 2])]
    #[QueryParam('page', 'integer', 'Page number to return. When omitted, all viable nodes are returned without pagination metadata.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Number of nodes to return per page when page is supplied, up to 100.', required: false, example: 50)]
    #[ResponseFromTransformer(NodeTransformer::class, Node::class, description: 'Deployable nodes returned.', collection: true, factoryStates: ['withLocation'], resourceKey: 'node')]
    #[ScribeResponse(self::NO_VIABLE_NODE_ERROR, status: 400, description: 'No nodes can satisfy the requested deployment constraints.')]
    public function __invoke(GetDeployableNodesRequest $request, FindViableNodesService $viableNodesService): array
    {
        $payload = $request->payload();

        $nodes = $viableNodesService
            ->setLocations($payload['location_ids'])
            ->setMemory($payload['memory'])
            ->setDisk($payload['disk'])
            ->handle($payload['per_page'], $payload['page']);

        return Fractal::collection($nodes)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toResponseArray();
    }
}
