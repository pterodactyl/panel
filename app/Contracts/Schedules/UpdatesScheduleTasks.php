<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Throwable;

interface UpdatesScheduleTasks
{
    /**
     * When the task's position changes, the tasks between its old and new positions shift to
     * keep the sequence contiguous. The input is validated here, and a backup task is refused
     * while the server cannot hold any backups.
     *
     * @param  array<string, mixed>  $input  action, payload, time_offset, and optionally sequence_id and continue_on_failure
     *
     * @throws HttpForbiddenException
     * @throws Throwable
     */
    public function update(Schedule $schedule, Task $task, array $input): Task;
}
