<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Throwable;

interface DeletesScheduleTasks
{
    /**
     * Deletes the task and closes the gap it leaves in the schedule's sequence.
     *
     * @throws Throwable
     */
    public function delete(Schedule $schedule, Task $task): void;
}
