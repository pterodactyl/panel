<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Schedule;
use Illuminate\Support\Collection;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ScheduleRepository extends EloquentRepository implements ScheduleRepositoryInterface
{
    /**
     * {@inheritdoc}
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
     * Return a schedule model with all of the associated tasks as a relationship.
     *
     * @param int $schedule
     * @return \Pterodactyl\Models\Schedule
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getScheduleWithTasks(int $schedule): Schedule
    {
        /** @var \Pterodactyl\Models\Schedule $instance */
        $instance = $this->getBuilder()->with('tasks')->find($schedule, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchedulesToProcess($timestamp)
    {
        return $this->getBuilder()->with('tasks')
            ->where('is_active', true)
            ->where('next_run_at', '<=', $timestamp)
            ->get($this->getColumns());
    }
}
