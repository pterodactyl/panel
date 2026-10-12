<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Schedules\DeletesScheduleTasks;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;

final readonly class DeleteScheduleTask implements DeletesScheduleTasks
{
    public function delete(Schedule $schedule, Task $task): void
    {
        DB::transaction(function () use ($schedule, $task): void {
            $schedule = $schedule->newQuery()->whereKey($schedule->getKey())->lockForUpdate()->firstOrFail();
            $task = $schedule->tasks()->whereKey($task->getKey())->firstOrFail();

            $schedule->tasks()
                ->where('sequence_id', '>', $task->sequence_id)
                ->decrement('sequence_id');

            $task->delete();
        });

        Activity::event('server:task.delete')->subject($schedule->server, $schedule, $task)->property('name', $schedule->name)->log();
    }
}
