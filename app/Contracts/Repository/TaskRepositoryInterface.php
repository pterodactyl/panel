<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Task;

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
    public function getTaskForJobProcess(int $id): Task;

    /**
     * Returns the next task in a schedule.
     *
     * @param int $schedule
     * @param int $index
     * @return null|\Pterodactyl\Models\Task
     */
    public function getNextTask(int $schedule, int $index);
}
