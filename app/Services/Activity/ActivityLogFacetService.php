<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Activity;

use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;

class ActivityLogFacetService
{
    /**
     * Returns the distinct events and acting users present in a log, so a client can
     * offer filter options that actually match something.
     *
     * @param  Builder<ActivityLog>  $query
     * @return array{events: list<string>, users: list<array{id: int, username: string, email: string}>}
     */
    public function handle(Builder $query): array
    {
        $query = (clone $query)->whereNotIn('activity_logs.event', ActivityLog::DISABLED_EVENTS);
        $events = JsonValueGuard::stringList((clone $query)->select('activity_logs.event')
            ->distinct()->orderBy('activity_logs.event')->pluck('event')->all());

        $actorIds = (clone $query)
            ->where('activity_logs.actor_type', (new User)->getMorphClass())
            ->whereNotNull('activity_logs.actor_id')
            ->select('activity_logs.actor_id')
            ->distinct()
            ->pluck('actor_id')
            ->all();

        $users = User::query()
            ->whereIn('id', $actorIds)
            ->orderBy('username')
            ->get(['id', 'username', 'email'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ])
            ->all();

        return ['events' => $events, 'users' => array_values($users)];
    }
}
