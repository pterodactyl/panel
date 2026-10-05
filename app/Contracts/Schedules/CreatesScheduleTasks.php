<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Exceptions\Service\ServiceLimitExceededException;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Throwable;

interface CreatesScheduleTasks
{
    /**
     * Appends a task to the schedule, or slots it into the requested position by
     * shifting the tasks that follow it.
     *
     * @param  TaskData  $data
     *
     * @throws ServiceLimitExceededException
     * @throws Throwable
     */
    public function create(Schedule $schedule, array $data): Task;
}
