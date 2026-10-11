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
     * Validates the input itself, so it can come straight from any caller.
     *
     * @param  ActionInput  $input  action, payload, time_offset, and optionally sequence_id and continue_on_failure
     *
     * @throws ServiceLimitExceededException
     * @throws Throwable
     */
    public function create(Schedule $schedule, array $input): Task;
}
