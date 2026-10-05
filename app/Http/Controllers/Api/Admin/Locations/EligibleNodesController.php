<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Locations;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Locations\GetLocationRequest;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Transformers\Api\Admin\NodeTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Locations', 'Create, update, retrieve, and delete physical or logical deployment locations.')]
class EligibleNodesController extends AdminApiController
{
    /**
     * List nodes eligible for location.
     *
     * @return ApiPayload
     */
    #[Endpoint('List eligible location nodes', 'Returns nodes assigned to a location that can be selected for location-scoped workflows.')]
    #[ResponseFromTransformer(NodeTransformer::class, Node::class, description: 'Eligible nodes returned.', collection: true, factoryStates: ['withLocation'], resourceKey: 'node')]
    public function __invoke(GetLocationRequest $request, Location $location): array
    {
        return Fractal::collection($location->nodes)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toResponseArray();
    }
}
