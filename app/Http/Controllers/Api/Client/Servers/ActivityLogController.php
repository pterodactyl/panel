<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Pterodactyl\Models\ActivityLog;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Transformers\Api\Client\ActivityLogTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class ActivityLogController extends ClientApiController
{
    /**
     * Returns the activity logs for a server.
     */
    public function __invoke(ClientApiRequest $request, Server $server): array
    {
        $this->authorize(Permission::ACTION_ACTIVITY_READ, $server);

        $activity = QueryBuilder::for($server->activity())
            ->with('actor')
            ->allowedSorts(['timestamp'])
            ->allowedFilters([AllowedFilter::partial('event')])
            ->whereNotIn('activity_logs.event', ActivityLog::DISABLED_EVENTS)
            ->when(config('activity.hide_admin_activity'), function (Builder $builder) use ($server) {
                // We could do this with a query and a lot of joins, but that gets pretty
                // painful so for now we'll execute a simpler query.
                $subusers = $server->subusers()->pluck('user_id')->merge($server->owner_id);

                $builder->select('activity_logs.*')
                    ->leftJoin('users', function (JoinClause $join) {
                        $join->on('users.id', 'activity_logs.actor_id')
                            ->where('activity_logs.actor_type', (new User())->getMorphClass());
                    })
                    ->where(function (Builder $builder) use ($subusers) {
                        $builder->whereNull('users.id')
                            ->orWhere('users.root_admin', 0)
                            ->orWhereIn('users.id', $subusers);
                    });
            })
            ->paginate(min($request->query('per_page', 25), 100))
            ->appends($request->query());

        return $this->fractal->collection($activity)
            ->transformWith($this->getTransformer(ActivityLogTransformer::class))
            ->toArray();
    }
}
