<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

interface TaskRepositoryInterface extends RepositoryInterface
{
    /**
     * Get a task and the server relationship for that task.
     *
     * @param int $id
     * @return \Pterodactyl\Models\Task
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getTaskWithServer($id);

    /**
     * Returns the next task in a schedule.
     *
     * @param int $schedule the ID of the schedule to select the next task from
     * @param int $index    the index of the current task
     * @return null|\Pterodactyl\Models\Task
     */
    public function getNextTask($schedule, $index);
}
