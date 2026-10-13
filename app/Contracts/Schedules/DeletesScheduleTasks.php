<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Throwable;

interface DeletesScheduleTasks
{
    /**
     * The gap the task leaves in the schedule's sequence is closed.
     *
     * @throws Throwable
     */
    public function delete(Schedule $schedule, Task $task): void;
}
