<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Schedules\UpdatesScheduleTasks;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Throwable;

final readonly class UpdateScheduleTask implements UpdatesScheduleTasks
{
    /**
     * Updates the task and, when its position changes, shifts the tasks between the
     * old and new positions to keep the sequence contiguous.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws HttpForbiddenException
     * @throws Throwable
     */
    public function update(Schedule $schedule, Task $task, array $input): Task
    {
        $attributes = CreateScheduleTask::attributes($schedule, $input);

        $task = DB::transaction(function () use ($schedule, $task, $attributes): Task {
            $schedule = $schedule->newQuery()->whereKey($schedule->getKey())->lockForUpdate()->firstOrFail();
            $task = $schedule->tasks()->whereKey($task->getKey())->firstOrFail();

            $sequenceId = $attributes['sequence_id'] ?? $task->sequence_id;

            if ($sequenceId < $task->sequence_id) {
                $schedule->tasks()
                    ->where('sequence_id', '>=', $sequenceId)
                    ->where('sequence_id', '<', $task->sequence_id)
                    ->increment('sequence_id');
            } elseif ($sequenceId > $task->sequence_id) {
                $schedule->tasks()
                    ->where('sequence_id', '>', $task->sequence_id)
                    ->where('sequence_id', '<=', $sequenceId)
                    ->decrement('sequence_id');
            }

            $task->update([...$attributes, 'sequence_id' => $sequenceId]);

            return $task;
        });

        Activity::event('server:task.update')
            ->subject($schedule->server, $schedule, $task)
            ->property(['name' => $schedule->name, 'action' => $task->action, 'payload' => $task->payload])
            ->log();

        return $task;
    }
}
