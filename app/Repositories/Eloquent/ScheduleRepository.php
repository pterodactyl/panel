<?php

namespace App\Repositories\Eloquent;

use App\Models\Schedule;
use Illuminate\Support\Collection;
use App\Exceptions\Repository\RecordNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Contracts\Repository\ScheduleRepositoryInterface;

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
     *
     * @param int $server
     * @return \Illuminate\Support\Collection
     */
    public function findServerSchedules(int $server): Collection
    {
        return $this->getBuilder()->withCount('tasks')->where('server_id', '=', $server)->get($this->getColumns());
    }

    /**
     * Load the tasks relationship onto the Schedule module if they are not
     * already present.
     *
     * @param \App\Models\Schedule $schedule
     * @param bool                         $refresh
     * @return \App\Models\Schedule
     */
    public function loadTasks(Schedule $schedule, bool $refresh = false): Schedule
    {
        if (! $schedule->relationLoaded('tasks') || $refresh) {
            $schedule->load('tasks');
        }

        return $schedule;
    }

    /**
     * Return a schedule model with all of the associated tasks as a relationship.
     *
     * @param int $schedule
     * @return \App\Models\Schedule
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function getScheduleWithTasks(int $schedule): Schedule
    {
        try {
            return $this->getBuilder()->with('tasks')->findOrFail($schedule, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException;
        }
    }

    /**
     * Return all of the schedules that should be processed.
     *
     * @param string $timestamp
     * @return \Illuminate\Support\Collection
     */
    public function getSchedulesToProcess(string $timestamp): Collection
    {
        return $this->getBuilder()->with('tasks')
            ->where('is_active', true)
            ->where('is_processing', false)
            ->where('next_run_at', '<=', $timestamp)
            ->get($this->getColumns());
    }
}
