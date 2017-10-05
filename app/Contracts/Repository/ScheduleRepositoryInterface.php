<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

interface ScheduleRepositoryInterface extends RepositoryInterface
{
    /**
     * Return all of the schedules for a given server.
     *
     * @param int $server
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getServerSchedules($server);

    /**
     * Return a schedule model with all of the associated tasks as a relationship.
     *
     * @param int $schedule
     * @return \Illuminate\Support\Collection
     */
    public function getScheduleWithTasks($schedule);

    /**
     * Return all of the schedules that should be processed.
     *
     * @param string $timestamp
     * @return \Illuminate\Support\Collection
     */
    public function getSchedulesToProcess($timestamp);
}
