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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use LogicException;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Events\ActivityLogged;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;
use Pterodactyl\Support\JsonValueGuard;

/**
 * \Pterodactyl\Models\ActivityLog.
 *
 * @property int $id
 * @property string|null $batch
 * @property string $event
 * @property string $ip
 * @property string|null $description
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property int|null $api_key_id
 * @property Collection<string, JsonValue> $properties
 * @property Carbon $timestamp
 * @property IlluminateModel|null $actor
 * @property \Illuminate\Database\Eloquent\Collection<int, ActivityLogSubject> $subjects
 * @property int|null $subjects_count
 * @property ApiKey|null $apiKey
 *
 * @method static Builder|ActivityLog forActor(\Illuminate\Database\Eloquent\Model $actor)
 * @method static Builder|ActivityLog forEvent(string $action)
 * @method static Builder|ActivityLog newModelQuery()
 * @method static Builder|ActivityLog newQuery()
 * @method static Builder|ActivityLog query()
 * @method static Builder|ActivityLog whereActorId($value)
 * @method static Builder|ActivityLog whereActorType($value)
 * @method static Builder|ActivityLog whereApiKeyId($value)
 * @method static Builder|ActivityLog whereBatch($value)
 * @method static Builder|ActivityLog whereDescription($value)
 * @method static Builder|ActivityLog whereEvent($value)
 * @method static Builder|ActivityLog whereId($value)
 * @method static Builder|ActivityLog whereIp($value)
 * @method static Builder|ActivityLog whereProperties($value)
 * @method static Builder|ActivityLog whereTimestamp($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
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

    /** @return array<string, JsonValue> */
    public function propertyValues(): array
    {
        $values = $this->properties->all();
        JsonValueGuard::assertValue($values);

        return $values;
    }

    /**
     * Returns models to be pruned.
     *
     * @see https://laravel.com/docs/9.x/eloquent#pruning-models
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        throw_if((config('activity.prune_days')) === null, LogicException::class, 'Cannot prune activity logs: no "prune_days" configuration value is set.');

        return static::query()->where('timestamp', '<=', now()->subDays(JsonValueGuard::integer(config('activity.prune_days'))));
    }

    /**
     * Boots the model event listeners. This will trigger an activity log event every
     * time a new model is inserted which can then be captured and worked with as needed.
     */
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
     * Scopes a query to only return results where the actor is a given model.
     *
     * @param  Builder<static>  $builder
     */
    #[Scope]
    protected function forActor(Builder $builder, IlluminateModel $actor): void
    {
        $builder->whereMorphedTo('actor', $actor);
    }
}
