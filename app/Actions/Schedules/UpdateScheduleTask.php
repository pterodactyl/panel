<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Schedules\UpdatesScheduleTasks;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Throwable;

final readonly class UpdateScheduleTask implements UpdatesScheduleTasks
{
    /**
     * Updates the task and, when its position changes, shifts the tasks between the
     * old and new positions to keep the sequence contiguous.
     *
     * @param  TaskData  $data
     *
     * @throws Throwable
     */
    public function update(Schedule $schedule, Task $task, array $data): Task
    {
        return DB::transaction(function () use ($schedule, $task, $data): Task {
            $schedule = $schedule->newQuery()->whereKey($schedule->getKey())->lockForUpdate()->firstOrFail();
            $task = $schedule->tasks()->whereKey($task->getKey())->firstOrFail();

            $sequenceId = max(1, $data['sequence_id'] ?? $task->sequence_id);

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

            $task->update([
                'sequence_id' => $sequenceId,
                'action' => $data['action'],
                'payload' => $data['payload'],
                'time_offset' => $data['time_offset'],
                'continue_on_failure' => $data['continue_on_failure'],
            ]);

            return $task->refresh();
        });
    }
}
