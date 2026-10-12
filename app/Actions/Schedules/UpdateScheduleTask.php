<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
        $validated = Validator::make($input, CreateScheduleTask::rules(($input['action'] ?? null) === Task::ACTION_POWER))->safe();
        throw_if($validated['action'] === Task::ACTION_BACKUP && $schedule->server->backup_limit === 0, HttpForbiddenException::class, "A backup task cannot be created when the server's backup limit is set to 0.");

        $attributes = [
            'action' => $validated['action'],
            'payload' => $validated['payload'] ?? '',
            'time_offset' => $validated['time_offset'],
            'continue_on_failure' => $validated['continue_on_failure'] ?? false,
        ];

        $requested = $validated->filled('sequence_id') ? $validated->integer('sequence_id') : null;

        $task = DB::transaction(function () use ($schedule, $task, $attributes, $requested): Task {
            $schedule = $schedule->newQuery()->whereKey($schedule->getKey())->lockForUpdate()->firstOrFail();
            $task = $schedule->tasks()->whereKey($task->getKey())->firstOrFail();

            $sequenceId = $requested ?? $task->sequence_id;

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
