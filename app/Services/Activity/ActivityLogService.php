<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Activity;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\ActivityLogSubject;
use Pterodactyl\Support\JsonEmptyObject;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

class ActivityLogService
{
    protected ?ActivityLog $activity = null;

    /** @var list<Model> */
    protected array $subjects = [];

    public function __construct(
        protected AuthFactory $manager,
        protected ActivityLogBatchService $batch,
        protected ActivityLogTargetableService $targetable,
        protected ConnectionInterface $connection,
        protected Request $request,
    ) {}

    /**
     * Sets the activity logger as having been caused by an anonymous
     * user type.
     */
    public function anonymous(): self
    {
        $this->getActivity()->actor_id = null;
        $this->getActivity()->actor_type = null;
        $this->getActivity()->setRelation('actor', null);

        return $this;
    }

    /**
     * Sets the action for this activity log.
     */
    public function event(string $action): self
    {
        $this->getActivity()->event = $action;

        return $this;
    }

    /**
     * Set the description for this activity.
     */
    public function description(?string $description): self
    {
        $this->getActivity()->description = $description;

        return $this;
    }

    /**
     * Sets the subject model instance(s) for this activity log entry. Null entries
     * are ignored, which lets callers pass an optional subject conditionally. A
     * non-Model Authenticatable (accepted here because auth events type their
     * user as the interface) is also skipped -- every Authenticatable this app
     * actually issues is a Model too, but this only ever tracks morphable models.
     *
     * @param  Model|Authenticatable|null  ...$subjects
     */
    public function subject(...$subjects): self
    {
        foreach ($subjects as $subject) {
            if (! $subject instanceof Model) {
                continue;
            }

            foreach ($this->subjects as $entry) {
                // If this subject is already tracked in our array of subjects just skip over
                // it and move on to the next one in the list.
                if ($entry->is($subject)) {
                    continue 2;
                }
            }

            $this->subjects[] = $subject;
        }

        return $this;
    }

    /**
     * Sets the actor model instance.
     */
    public function actor(Model $actor): self
    {
        $this->getActivity()->actor()->associate($actor);

        return $this;
    }

    /**
     * Sets a custom property on the activity log instance.
     *
     * @param  string|array<string, JsonValue>  $key
     * @param  JsonInputValue  $value  Ignored when an array of properties is provided.
     */
    public function property(array|string $key, array|bool|float|int|string|JsonEmptyObject|null $value = null): self
    {
        $activity = $this->getActivity();
        $properties = $activity->properties;
        if (is_array($key)) {
            foreach ($key as $name => $property) {
                $properties->put($name, $property);
            }
        } else {
            JsonValueGuard::assertValue($value);
            $properties->put($key, $value);
        }

        $activity->properties = $properties;

        return $this;
    }

    /**
     * Attaches the instance request metadata to the activity log event.
     */
    public function withRequestMetadata(): self
    {
        return $this->property([
            'ip' => $this->request->ip(),
            'useragent' => $this->request->userAgent(),
        ]);
    }

    /**
     * Logs an activity log entry with the set values and then returns the
     * model instance to the caller. If there is an exception encountered while
     * performing this action it will be logged to the disk but will not interrupt
     * the code flow.
     */
    public function log(?string $description = null): ActivityLog
    {
        $activity = $this->getActivity();

        if (($description) !== null) {
            $activity->description = $description;
        }

        try {
            return $this->save();
        } catch (Throwable $throwable) {
            /* @noinspection PhpUnhandledExceptionInspection */
            throw_if(config('app.env') !== 'production', $throwable);

            Log::error($throwable->getMessage(), ['exception' => $throwable]);
        }

        return $activity;
    }

    /**
     * Returns a cloned instance of the service allowing for the creation of a base
     * activity log with the ability to change values on the fly without impact.
     */
    public function clone(): self
    {
        return clone $this;
    }

    /**
     * Executes the provided callback within the scope of a database transaction
     * and will only save the activity log entry if everything else successfully
     * settles.
     *
     * @template TReturn of object|ApiValue10
     *
     * @param  Closure($this): TReturn  $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    public function transaction(Closure $callback): array|bool|float|int|object|string|null
    {
        return $this->connection->transaction(function () use ($callback) {
            $response = $callback($this);

            $this->save();

            return $response;
        });
    }

    /**
     * Resets the instance and clears out the log.
     */
    public function reset(): void
    {
        $this->activity = null;
        $this->subjects = [];
    }

    /**
     * Returns the current activity log instance.
     */
    protected function getActivity(): ActivityLog
    {
        if ($this->activity instanceof ActivityLog) {
            return $this->activity;
        }

        $this->activity = new ActivityLog([
            'ip' => $this->request->ip(),
            'batch' => $this->batch->uuid(),
            'properties' => Collection::make([]),
            'api_key_id' => $this->targetable->apiKeyId(),
        ]);

        if (($subject = $this->targetable->subject()) instanceof Model) {
            $this->subject($subject);
        }

        if (($actor = $this->targetable->actor()) instanceof Model) {
            $this->actor($actor);
        } elseif (($user = $this->manager->guard()->user()) !== null) {
            $this->actor($user);
        }

        return $this->activity;
    }

    /**
     * Saves the activity log instance and attaches all of the subject models.
     *
     * @throws Throwable
     */
    protected function save(): ActivityLog
    {
        throw_unless($this->activity instanceof ActivityLog, InvalidArgumentException::class, 'Cannot save an activity log before one has been initialized.');

        $activity = $this->activity;
        $response = $this->connection->transaction(function () use ($activity): ActivityLog {
            $activity->save();

            $subjects = Collection::make($this->subjects)
                ->map(fn (Model $subject): array => [
                    'activity_log_id' => $activity->id,
                    'subject_id' => $subject->getKey(),
                    'subject_type' => $subject->getMorphClass(),
                ])
                ->values()
                ->all();

            ActivityLogSubject::query()->insert($subjects);

            return $activity;
        });

        $this->activity = null;
        $this->subjects = [];

        return $response;
    }
}
