<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Pterodactyl\Services\Deployment\FindViableNodesService;
use Pterodactyl\Transformers\Api\Application\NodeTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Nodes\GetDeployableNodesRequest;

class NodeDeploymentController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Deployment\FindViableNodesService
     */
    private $viableNodesService;

    /**
     * NodeDeploymentController constructor.
     *
     * @param \Pterodactyl\Services\Deployment\FindViableNodesService $viableNodesService
     */
    public function __construct(FindViableNodesService $viableNodesService)
    {
        parent::__construct();

        $this->viableNodesService = $viableNodesService;
    }

    /**
     * Finds any nodes that are available using the given deployment criteria. This works
     * similarly to the server creation process, but allows you to pass the deployment object
     * to this endpoint and get back a list of all Nodes satisfying the requirements.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nodes\GetDeployableNodesRequest $request
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     */
    public function __invoke(GetDeployableNodesRequest $request): array
    {
        $data = $request->validated();
        $nodes = $this->viableNodesService->setLocations($data['location_ids'] ?? [])
            ->setMemory($data['memory'])
            ->setDisk($data['disk'])
            ->handle($request->input('page') ?? 0);

        return $this->fractal->collection($nodes)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toArray();
    }
}
