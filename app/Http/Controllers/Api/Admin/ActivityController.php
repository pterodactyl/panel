<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Requests\Api\Admin\Activity\GetActivityLogsRequest;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Support\ActivityLogFilters;
use Pterodactyl\Transformers\Api\Admin\ActivityLogTransformer;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Activity', 'Panel-wide activity log covering every actor and subject.')]
class ActivityController extends AdminApiController
{
    /**
     * Actions performed through the admin panel are namespaced with this prefix, which
     * is what separates them from the customer activity shown on a server or account.
     */
    public const string ADMIN_EVENT_PREFIX = 'admin:';

    /**
     * List activity.
     *
     * @return ApiPayload
     */
    #[Endpoint('List activity', 'Returns a paginated list of administrative activity. Only events in the "admin" namespace are returned; customer activity against a server or account is excluded.')]
    #[QueryParam('filter[event]', 'string', 'Filter activity by event name.', required: false, example: 'admin:server.build')]
    #[QueryParam('filter[ip]', 'string', 'Filter activity by originating IP address.', required: false, example: '192.0.2.10')]
    #[QueryParam('filter[user]', 'string', 'Filter activity by the username or email of the user that performed it.', required: false, example: 'admin@example.com')]
    #[QueryParam('filter[user_id]', 'integer', 'Filter activity to a single user by ID. Preferred over filter[user] when the exact user is known.', required: false, example: 1)]
    #[QueryParam('filter[event_name]', 'string', 'Filter activity to one exact event name. Preferred over filter[event], which matches substrings.', required: false, example: 'admin:server.build')]
    #[QueryParam('sort', 'string', 'Sort activity by timestamp. Prefix with "-" for descending order.', required: false, example: '-timestamp', enum: ['timestamp', '-timestamp'])]
    #[QueryParam('page', 'integer', 'Page number to return.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page. The maximum is 100.', required: false, example: 25)]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports actor.', required: false, example: 'actor')]
    #[ResponseFromTransformer(ActivityLogTransformer::class, ActivityLog::class, description: 'Activity logs returned.', collection: true, factoryStates: ['withActor'], resourceKey: 'activity_log', paginate: [IlluminatePaginatorAdapter::class, 25], include: ['actor'])]
    public function __invoke(GetActivityLogsRequest $request): array
    {
        $activity = QueryBuilder::for(ActivityLog::query()->where('event', 'like', self::ADMIN_EVENT_PREFIX.'%'))
            ->allowedFilters([AllowedFilter::partial('event'), AllowedFilter::partial('ip'), ActivityLogFilters::actor($request->string('filter.user')->toString()), ActivityLogFilters::actorId($request->integer('filter.user_id')), AllowedFilter::exact('event_name', 'event')])
            ->allowedSorts(['timestamp'])
            ->defaultSort('-timestamp', '-id')
            ->with('actor')
            ->whereNotIn('activity_logs.event', ActivityLog::DISABLED_EVENTS)
            ->paginate($request->perPage(25))
            ->appends($request->query());

        return Fractal::collection($activity)
            ->transformWith($this->getTransformer(ActivityLogTransformer::class))
            ->toResponseArray();
    }
}
