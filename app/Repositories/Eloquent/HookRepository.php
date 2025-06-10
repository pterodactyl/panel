<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class HookRepository extends EloquentRepository
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return Hook::class;
    }

    /**
     * Return all the schedules for a given server.
     */
    public function findServerHooks(int $server): Collection
    {
        return $this->getBuilder()->withCount('triggers')->where('server_id', '=', $server)->get($this->getColumns());
    }

    /**
     * Return a schedule model with all the associated tasks as a relationship.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getHookWithTriggerAndAction(int $hook): Hook
    {
        try {
            return $this->getBuilder()->with('tasks')->findOrFail($schedule, $this->getColumns());
        } catch (ModelNotFoundException) {
            throw new RecordNotFoundException();
        }
    }
}
