<?php

namespace Pterodactyl\Events;

use Illuminate\Support\Str;
use Pterodactyl\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogged extends Event
{
    public function __construct(public ActivityLog $model)
    {
    }

    public function is(string $event): bool
    {
        return $this->model->event === $event;
    }

    public function actor(): ?Model
    {
        return $this->isSystem() ? null : $this->model->actor;
    }

    public function isServerEvent(): bool
    {
        return Str::startsWith($this->model->event, 'server:');
    }

    public function isSystem(): bool
    {
        return is_null($this->model->actor_id);
    }
}
