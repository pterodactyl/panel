<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Activity;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;

class ActivityLogQueryService
{
    /** @return Builder<ActivityLog> */
    public function forServer(Server $server): Builder
    {
        $query = $server->activity()->getQuery()->select('activity_logs.*')
            ->whereNotIn('activity_logs.event', ActivityLog::DISABLED_EVENTS);

        if (config('activity.hide_admin_activity')) {
            $subusers = $server->subusers()->pluck('user_id')->merge([$server->owner_id]);

            $query->leftJoin('users', function (JoinClause $join): void {
                $join->on('users.id', 'activity_logs.actor_id')
                    ->where('activity_logs.actor_type', (new User)->getMorphClass());
            })->where(function (Builder $builder) use ($subusers): void {
                $builder->whereNull('users.id')
                    ->orWhere('users.root_admin', 0)
                    ->orWhereIn('users.id', $subusers);
            });
        }

        return $query;
    }
}
