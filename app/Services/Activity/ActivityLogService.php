<?php

namespace Pterodactyl\Services\Activity;

use Illuminate\Support\Collection;
use Pterodactyl\Models\ActivityLog;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\ConnectionInterface;

class ActivityLogService
{
    protected ?ActivityLog $activity = null;

    protected Factory $manager;
    protected ConnectionInterface $connection;
    protected AcitvityLogBatchService $batch;
    protected ActivityLogTargetableService $targetable;

    public function __construct(
        Factory $manager,
        AcitvityLogBatchService $batch,
        ActivityLogTargetableService $targetable,
        ConnectionInterface $connection
    ) {
        $this->manager = $manager;
        $this->batch = $batch;
        $this->targetable = $targetable;
        $this->connection = $connection;
    }

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
     * Sets the subject model instance.
     */
    public function subject(Model $subject): self
    {
        $this->getActivity()->subject()->associate($subject);

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
     * Sets the custom properties for the activity log instance.
     *
     * @param \Illuminate\Support\Collection|array $properties
     */
    public function withProperties($properties): self
    {
        $this->getActivity()->properties = Collection::make($properties);

        return $this;
    }

    /**
     * Sets a custom property on the activty log instance.
     *
     * @param mixed $value
     */
    public function property(string $key, $value): self
    {
        $this->getActivity()->properties = $this->getActivity()->properties->put($key, $value);

        return $this;
    }

    /**
     * Attachs the instance request metadata to the activity log event.
     */
    public function withRequestMetadata(): self
    {
        $this->property('ip', Request::getClientIp());
        $this->property('useragent', Request::userAgent());

        return $this;
    }

    /**
     * Logs an activity log entry with the set values and then returns the
     * model instance to the caller.
     */
    public function log(string $description = null): ActivityLog
    {
        $activity = $this->getActivity();

        if (!is_null($description)) {
            $activity->description = $description;
        }

        $activity->save();

        $this->activity = null;

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
     * and will only save the activity log entry if everything else succesfully
     * settles.
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(\Closure $callback, string $description = null)
    {
        if (!is_null($description)) {
            $this->description($description);
        }

        return $this->connection->transaction(function () use ($callback) {
            $response = $callback($activity = $this->getActivity());

            $activity->save();
            $this->activity = null;

            return $response;
        });
    }

    /**
     * Returns the current activity log instance.
     */
    protected function getActivity(): ActivityLog
    {
        if ($this->activity) {
            return $this->activity;
        }

        $this->activity = new ActivityLog([
            'ip' => Request::ip(),
            'batch_uuid' => $this->batch->uuid(),
            'properties' => Collection::make([]),
        ]);

        if ($subject = $this->targetable->subject()) {
            $this->subject($subject);
        }

        if ($actor = $this->targetable->actor()) {
            $this->actor($actor);
        } elseif ($user = $this->manager->guard()->user()) {
            if ($user instanceof Model) {
                $this->actor($user);
            }
        }

        return $this->activity;
    }
}
