<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Requests\Api\Admin\Activity\GetActivityLogsRequest;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Services\Activity\ActivityLogFacetService;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Activity', 'Panel-wide activity log covering every actor and subject.')]
class ActivityFilterController extends AdminApiController
{
    public const array FACETS_EXAMPLE = [
        'data' => [
            'events' => ['admin:server.build', 'admin:server.suspend'],
            'users' => [
                ['id' => 1, 'username' => 'admin', 'email' => 'admin@example.com'],
            ],
        ],
    ];

    /**
     * Return the filter options available for the administrative activity log.
     */
    #[Endpoint('List activity filter options', 'Returns the distinct events and users present in the administrative activity log.')]
    #[ScribeResponse(self::FACETS_EXAMPLE, description: 'Filter options returned.')]
    public function __invoke(GetActivityLogsRequest $request, ActivityLogFacetService $facets): JsonResponse
    {
        return new JsonResponse([
            'data' => $facets->handle(ActivityLog::query()->where('event', 'like', ActivityController::ADMIN_EVENT_PREFIX.'%')),
        ]);
    }
}
