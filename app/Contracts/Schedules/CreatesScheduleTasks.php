<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Exceptions\Service\ServiceLimitExceededException;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Throwable;

interface CreatesScheduleTasks
{
    /**
     * Append the task to the schedule, or slot it into the requested position by shifting the
     * tasks that follow it. The input is validated here, and a backup task is refused while the
     * server cannot hold any backups.
     *
     * @param  array<string, mixed>  $input  action, payload, time_offset, and optionally sequence_id and continue_on_failure
     *
     * @throws HttpForbiddenException
     * @throws ServiceLimitExceededException
     * @throws Throwable
     */
    public function create(Schedule $schedule, array $input): Task;
}
