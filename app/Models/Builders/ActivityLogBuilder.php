<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\User;

/**
 * @template TModel of ActivityLog
 *
 * @extends Builder<TModel>
 */
class ActivityLogBuilder extends Builder
{
    public function withoutDisabledEvents(): static
    {
        $this->whereNotIn('activity_logs.event', ActivityLog::DISABLED_EVENTS);

        return $this;
    }

    /**
     * The distinct events and acting users in the log, so a client can offer filter options
     * that match something.
     *
     * @return array{events: list<string>, users: list<array{id: int, username: string, email: string}>}
     */
    public function facets(): array
    {
        $query = (clone $this)->withoutDisabledEvents();
        $events = (clone $query)->select('activity_logs.event')
            ->distinct()->orderBy('activity_logs.event')->withoutEagerLoads()->get()
            ->map(fn (ActivityLog $log): string => $log->event)
            ->all();

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

        return ['events' => array_values($events), 'users' => array_values($users)];
    }
}
