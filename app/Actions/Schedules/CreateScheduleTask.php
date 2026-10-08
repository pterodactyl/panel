<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Schedules\CreatesScheduleTasks;
use Pterodactyl\Exceptions\Service\ServiceLimitExceededException;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

final readonly class CreateScheduleTask implements CreatesScheduleTasks
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
    public function create(Schedule $schedule, array $data): Task
    {
        return DB::transaction(function () use ($schedule, $data): Task {
            $schedule = $schedule->newQuery()->whereKey($schedule->getKey())->lockForUpdate()->firstOrFail();
            $limit = JsonValueGuard::integer(config('pterodactyl.client_features.schedules.per_schedule_task_limit', 10));
            throw_if($schedule->tasks()->count() >= $limit, ServiceLimitExceededException::class, "Schedules may not have more than $limit tasks associated with them. Creating this task would put this schedule over the limit.");

            $next = ($schedule->tasks()->orderByDesc('sequence_id')->first()->sequence_id ?? 0) + 1;
            $requested = max(1, $data['sequence_id'] ?? $next);

            $sequenceId = $next;
            if ($requested < $next) {
                $schedule->tasks()
                    ->where('sequence_id', '>=', $requested)
                    ->increment('sequence_id');
                $sequenceId = $requested;
            }

            return $schedule->tasks()->create([
                'sequence_id' => $sequenceId,
                'action' => $data['action'],
                'payload' => $data['payload'],
                'time_offset' => $data['time_offset'],
                'continue_on_failure' => $data['continue_on_failure'],
            ]);
        });
    }
}
