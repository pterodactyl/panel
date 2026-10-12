<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use LogicException;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Events\ActivityLogged;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;

/**
 * @property Collection<string, mixed> $properties
 */
#[Attributes\Identifiable('actl')]
#[Guarded([
    'id',
    'timestamp',
])]
#[WithoutTimestamps]
class ActivityLog extends Model implements Identifiable
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    use HasRealtimeIdentifier;
    use MassPrunable;

    public const string RESOURCE_NAME = 'activity_log';

    /**
     * Tracks all the events we no longer wish to display to users. These are either legacy
     * events or just events where we never ended up using the associated data.
     */
    public const array DISABLED_EVENTS = ['server:file.upload'];

    protected $with = ['subjects'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'collection',
            'timestamp' => 'datetime',
        ];
    }

    /**
     * The distinct events and acting users in a log, so a client can offer filter options
     * that match something.
     *
     * @param  Builder<self>  $query
     * @return array{events: list<string>, users: list<array{id: int, username: string, email: string}>}
     */
    public static function facets(Builder $query): array
    {
        $query = (clone $query)->whereNotIn('activity_logs.event', self::DISABLED_EVENTS);
        $events = (clone $query)->select('activity_logs.event')
            ->distinct()->orderBy('activity_logs.event')->withoutEagerLoads()->get()
            ->map(fn (self $log): string => $log->event)
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

    /**
     * @return MorphTo<IlluminateModel, $this>
     */
    public function actor(): MorphTo
    {
        $morph = $this->morphTo();
        if (method_exists($morph, 'withTrashed')) { // @phpstan-ignore function.alreadyNarrowedType
            return $morph->withTrashed();
        }

        return $morph;
    }

    /**
     * @return HasMany<ActivityLogSubject, $this>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(ActivityLogSubject::class);
    }

    /**
     * @return HasOne<ApiKey, $this>
     */
    public function apiKey(): HasOne
    {
        return $this->hasOne(ApiKey::class, 'id', 'api_key_id');
    }

    /** @return array<string, mixed> */
    public function propertyValues(): array
    {
        return $this->properties->all();
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        $days = config('activity.prune_days');
        throw_unless($days > 0, LogicException::class, 'Cannot prune activity logs: no "prune_days" configuration value is set.');

        return static::query()->where('timestamp', '<=', now()->subDays($days));
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (self $model): void {
            Event::dispatch(new ActivityLogged($model));
        });
    }

    /**
     * @param  Builder<static>  $builder
     */
    #[Scope]
    protected function forEvent(Builder $builder, string $action): void
    {
        $builder->where('event', $action);
    }

    /**
     * @param  Builder<static>  $builder
     */
    #[Scope]
    protected function forActor(Builder $builder, IlluminateModel $actor): void
    {
        $builder->whereMorphedTo('actor', $actor);
    }
}
