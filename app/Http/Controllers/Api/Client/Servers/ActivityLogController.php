<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\GetActivityLogsRequest;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Activity\ActivityLogQueryService;
use Pterodactyl\Support\ActivityLogFilters;
use Pterodactyl\Transformers\Api\Client\ActivityLogTransformer;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Overview', 'Inspect and control an accessible server.')]
class ActivityLogController extends ClientApiController
{
    /**
     * Returns the activity logs for a server.
     *
     * @return ApiPayload
     */
    #[Endpoint('List server activity', 'Returns a paginated list of activity log entries for the server.')]
    #[QueryParam('page', 'integer', 'Page number to retrieve.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Number of activity log entries to return per page. The maximum is 100.', required: false, example: 25)]
    #[QueryParam('filter[event]', 'string', 'Filter activity by event name.', required: false, example: 'server:power.start')]
    #[QueryParam('filter[user]', 'string', 'Filter activity by the username or email of the user that performed it.', required: false, example: 'admin@example.com')]
    #[QueryParam('filter[user_id]', 'integer', 'Filter activity to a single user by ID. Preferred over filter[user] when the exact user is known.', required: false, example: 1)]
    #[QueryParam('filter[event_name]', 'string', 'Filter activity to one exact event name. Preferred over filter[event], which matches substrings.', required: false, example: 'admin:server.build')]
    #[QueryParam('sort', 'string', 'Sort activity by timestamp. Prefix with "-" for descending order.', required: false, example: '-timestamp', enum: ['timestamp', '-timestamp'])]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports actor.', required: false, example: 'actor')]
    #[ResponseFromTransformer(ActivityLogTransformer::class, ActivityLog::class, description: 'Server activity logs returned.', collection: true, factoryStates: ['withActor'], resourceKey: 'activity_log', paginate: [IlluminatePaginatorAdapter::class, 25], include: ['actor'])]
    public function __invoke(GetActivityLogsRequest $request, ActivityLogQueryService $logs, Server $server): array
    {
        $this->authorize(Permissions::ActivityRead->value, $server);

        $query = QueryBuilder::for($logs->forServer($server))
            ->allowedSorts(['timestamp'])
            ->allowedFilters([AllowedFilter::partial('event'), ActivityLogFilters::actor($request->string('filter.user')->toString()), ActivityLogFilters::actorId($request->integer('filter.user_id')), AllowedFilter::exact('event_name', 'event')]);

        $builder = $query->getEloquentBuilder()
            ->select('activity_logs.*')
            ->with('actor')
            ->whereNotIn('activity_logs.event', ActivityLog::DISABLED_EVENTS);

        $activity = $builder
            ->paginate(min($request->integer('per_page', 25), 100))
            ->appends($request->query());

        return Fractal::collection($activity)
            ->transformWith($this->getTransformer(ActivityLogTransformer::class))
            ->toResponseArray();
    }
}
