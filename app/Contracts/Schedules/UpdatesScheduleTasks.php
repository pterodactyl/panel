<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Throwable;

interface UpdatesScheduleTasks
{
    /**
     * Updates the task and, when its position changes, shifts the tasks between the
     * old and new positions to keep the sequence contiguous.
     *
     * @param  TaskData  $data
     *
     * @throws Throwable
     */
    public function update(Schedule $schedule, Task $task, array $data): Task;
}
