<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Admin\ActivityFilterController;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Account', 'Manage the authenticated user account.')]
class ActivityLogFilterController extends ClientApiController
{
    /**
     * Return the filter options available for the account activity log.
     */
    #[Endpoint('List account activity filter options', 'Returns the distinct events and users present in the account activity log.')]
    #[ScribeResponse(ActivityFilterController::FACETS_EXAMPLE, description: 'Filter options returned.')]
    public function __invoke(ClientApiRequest $request): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->authenticatedUser($request)->activity()->facets(),
        ]);
    }
}
