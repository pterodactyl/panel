<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Controllers\Api\Admin\ActivityFilterController;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Activity\ActivityLogFacetService;
use Pterodactyl\Services\Activity\ActivityLogQueryService;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Overview', 'Inspect and control an accessible server.')]
class ActivityLogFilterController extends ClientApiController
{
    /**
     * Return the filter options available for a server activity log.
     */
    #[Endpoint('List server activity filter options', 'Returns the distinct events and users present in the server activity log.')]
    #[ScribeResponse(ActivityFilterController::FACETS_EXAMPLE, description: 'Filter options returned.')]
    public function __invoke(ClientApiRequest $request, ActivityLogFacetService $facets, ActivityLogQueryService $logs, Server $server): JsonResponse
    {
        $this->authorize(Permissions::ActivityRead->value, $server);

        return new JsonResponse(['data' => $facets->handle($logs->forServer($server))]);
    }
}
