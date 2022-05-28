<?php

namespace Pterodactyl\Services\Activity;

use Illuminate\Support\Collection;
use Pterodactyl\Models\ActivityLog;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Database\Eloquent\Model;
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
    public function withDescription(?string $description): self
    {
        $this->getActivity()->description = $description;

        return $this;
    }

    /**
     * Sets the subject model instance.
     */
    public function withSubject(Model $subject): self
    {
        $this->getActivity()->subject()->associate($subject);

        return $this;
    }

    /**
     * Sets the actor model instance.
     */
    public function withActor(Model $actor): self
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
    public function withProperty(string $key, $value): self
    {
        $this->getActivity()->properties = $this->getActivity()->properties->put($key, $value);

        return $this;
    }

    /**
     * Logs an activity log entry with the set values and then returns the
     * model instance to the caller.
     */
    public function log(string $description): ActivityLog
    {
        $this->withDescription($description);

        $activity = $this->activity;
        $activity->save();
        $this->activity = null;

        return $activity;
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
            $this->withDescription($description);
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
            'batch_uuid' => $this->batch->uuid(),
            'properties' => Collection::make([]),
        ]);

        if ($subject = $this->targetable->subject()) {
            $this->withSubject($subject);
        }

        if ($actor = $this->targetable->actor()) {
            $this->withActor($actor);
        } elseif ($user = $this->manager->guard()->user()) {
            if ($user instanceof Model) {
                $this->withActor($user);
            }
        }

        return $this->activity;
    }
}
