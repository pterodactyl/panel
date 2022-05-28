<?php

namespace Pterodactyl\Services\Activity;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;

class ActivityLogTargetableService
{
    protected ?Model $actor = null;

    protected ?Model $subject = null;

    public function setActor(Model $actor): void
    {
        if (!is_null($this->actor)) {
            throw new InvalidArgumentException('Cannot call ' . __METHOD__ . ' when an actor is already set on the instance.');
        }

        $this->actor = $actor;
    }

    public function setSubject(Model $subject): void
    {
        if (!is_null($this->subject)) {
            throw new InvalidArgumentException('Cannot call ' . __METHOD__ . ' when a target is already set on the instance.');
        }

        $this->subject = $subject;
    }

    public function actor(): ?Model
    {
        return $this->actor;
    }

    public function subject(): ?Model
    {
        return $this->subject;
    }

    public function reset(): void
    {
        $this->actor = null;
        $this->subject = null;
    }
}
