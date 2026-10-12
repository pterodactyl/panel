<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Schedules\CreatesScheduleTasks;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Exceptions\Service\ServiceLimitExceededException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;

final readonly class CreateScheduleTask implements CreatesScheduleTasks
{
    /**
     * A power task's payload must name the power action to send.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(bool $power): array
    {
        $payload = ['required_unless:action,backup', 'string', 'nullable'];
        if ($power) {
            $payload[] = Rule::in(Task::POWER_ACTIONS);
        }

        return [
            'action' => ['required', Rule::in([Task::ACTION_COMMAND, Task::ACTION_POWER, Task::ACTION_BACKUP])],
            'payload' => $payload,
            'time_offset' => ['required', 'integer', 'min:0', 'max:900'],
            'sequence_id' => ['sometimes', 'required', 'integer', 'min:1'],
            'continue_on_failure' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function create(Schedule $schedule, array $input): Task
    {
        $validated = Validator::make($input, self::rules(($input['action'] ?? null) === Task::ACTION_POWER))->safe();
        throw_if($validated['action'] === Task::ACTION_BACKUP && $schedule->server->backup_limit === 0, HttpForbiddenException::class, "A backup task cannot be created when the server's backup limit is set to 0.");

        $attributes = [
            'action' => $validated['action'],
            'payload' => $validated['payload'] ?? '',
            'time_offset' => $validated['time_offset'],
            'continue_on_failure' => $validated['continue_on_failure'] ?? false,
        ];

        $requested = $validated->filled('sequence_id') ? $validated->integer('sequence_id') : null;

        $task = DB::transaction(function () use ($schedule, $attributes, $requested): Task {
            $schedule = $schedule->newQuery()->whereKey($schedule->getKey())->lockForUpdate()->firstOrFail();
            $limit = config('pterodactyl.client_features.schedules.per_schedule_task_limit');
            throw_if($schedule->tasks()->count() >= $limit, ServiceLimitExceededException::class, "Schedules may not have more than $limit tasks associated with them. Creating this task would put this schedule over the limit.");

            $next = ($schedule->tasks()->orderByDesc('sequence_id')->first()->sequence_id ?? 0) + 1;
            $requested ??= $next;

            $sequenceId = $next;
            if ($requested < $next) {
                $schedule->tasks()
                    ->where('sequence_id', '>=', $requested)
                    ->increment('sequence_id');
                $sequenceId = $requested;
            }

            return $schedule->tasks()->create([...$attributes, 'sequence_id' => $sequenceId]);
        });

        Activity::event('server:task.create')
            ->subject($schedule->server, $schedule, $task)
            ->property(['name' => $schedule->name, 'action' => $task->action, 'payload' => $task->payload])
            ->log();

        return $task;
    }
}
