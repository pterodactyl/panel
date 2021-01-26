<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ScheduleRepository extends EloquentRepository implements ScheduleRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Schedule::class;
    }

    /**
     * Return all of the schedules for a given server.
     */
    public function findServerSchedules(int $server): Collection
    {
        return $this->getBuilder()->withCount('tasks')->where('server_id', '=', $server)->get($this->getColumns());
    }

    /**
     * Return a schedule model with all of the associated tasks as a relationship.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getScheduleWithTasks(int $schedule): Schedule
    {
        try {
            return $this->getBuilder()->with('tasks')->findOrFail($schedule, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException();
        }
    }
}
