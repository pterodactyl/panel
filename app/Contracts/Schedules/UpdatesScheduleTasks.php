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
     * Validates the input itself, so it can come straight from any caller.
     *
     * @param  array<string, mixed>  $input  action, payload, time_offset, and optionally sequence_id and continue_on_failure
     *
     * @throws Throwable
     */
    public function update(Schedule $schedule, Task $task, array $input): Task;
}
