<?php

declare(strict_types=1);

namespace Pterodactyl\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Spatie\QueryBuilder\AllowedFilter;

class ActivityLogFilters
{
    public static function actor(string $term): AllowedFilter
    {
        return AllowedFilter::callback('user', function (Builder $query) use ($term): void {
            $term = Str::trim($term);
            if ($term === '') {
                return;
            }

            $query->where('activity_logs.actor_type', (new User)->getMorphClass())
                ->whereIn('activity_logs.actor_id', User::query()
                    ->where(function (Builder $builder) use ($term): void {
                        $builder->where('username', 'like', '%'.$term.'%')
                            ->orWhere('email', 'like', '%'.$term.'%');
                    })
                    ->select('id'));
        });
    }

    public static function actorId(int $id): AllowedFilter
    {
        return AllowedFilter::callback('user_id', function (Builder $query) use ($id): void {
            $query->where('activity_logs.actor_type', (new User)->getMorphClass())
                ->where('activity_logs.actor_id', $id);
        });
    }
}
