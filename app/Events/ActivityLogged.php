<?php

namespace Pterodactyl\Events;

use Illuminate\Support\Str;
use Pterodactyl\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogged extends Event
{
    public ActivityLog $model;

    public function __construct(ActivityLog $model)
    {
        $this->model = $model;
    }

    public function is(string $event): bool
    {
        return $this->model->event === $event;
    }

    public function actor(): ?Model
    {
        return $this->isSystem() ? null : $this->model->actor;
    }

    public function isServerEvent()
    {
        return Str::startsWith($this->model->event, 'server:');
    }

    public function isSystem()
    {
        return is_null($this->model->actor_id);
    }
}
