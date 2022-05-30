<?php

namespace Pterodactyl\Services\Activity;

use Illuminate\Database\Eloquent\Model;

class ActivityLogTargetableService
{
    protected ?Model $actor = null;

    protected ?Model $subject = null;

    public function setActor(Model $actor): void
    {
        $this->actor = $actor;
    }

    public function setSubject(Model $subject): void
    {
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
