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
     * {@inheritdoc}
     */
    public function getServerSchedules($server)
    {
        return $this->getBuilder()->withCount('tasks')->where('server_id', '=', $server)->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduleWithTasks($schedule)
    {
        return $this->getBuilder()->with('tasks')->find($schedule, $this->getColumns());
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
