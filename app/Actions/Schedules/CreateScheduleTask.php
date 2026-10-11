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
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

final readonly class CreateScheduleTask implements CreatesScheduleTasks
{
    /**
     * A task's fields, for creating one and for updating one. A power task's payload
     * must name the power action to send.
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

    /**
     * Validates task input for a schedule and returns the model attributes it describes.
     * A backup task is refused while the server cannot hold any backups.
     *
     * @param  ActionInput  $input
     * @return array{action: string, payload: string, time_offset: int, continue_on_failure: bool, sequence_id: int|null}
     *
     * @throws HttpForbiddenException
     */
    public static function attributes(Schedule $schedule, array $input): array
    {
        $input = Validator::make($input, self::rules(($input['action'] ?? null) === Task::ACTION_POWER))->safe();
        $action = $input->string('action')->toString();

        throw_if($action === Task::ACTION_BACKUP && $schedule->server->backup_limit === 0, HttpForbiddenException::class, "A backup task cannot be created when the server's backup limit is set to 0.");

        return [
            'action' => $action,
            'payload' => $input->string('payload')->toString(),
            'time_offset' => $input->integer('time_offset'),
            'continue_on_failure' => $input->boolean('continue_on_failure'),
            'sequence_id' => $input->filled('sequence_id') ? $input->integer('sequence_id') : null,
        ];
    }

    /**
     * Appends a task to the schedule, or slots it into the requested position by
     * shifting the tasks that follow it.
     *
     * @param  ActionInput  $input
     *
     * @throws HttpForbiddenException
     * @throws ServiceLimitExceededException
     * @throws Throwable
     */
    public function create(Schedule $schedule, array $input): Task
    {
        $attributes = self::attributes($schedule, $input);

        $task = DB::transaction(function () use ($schedule, $attributes): Task {
            $schedule = $schedule->newQuery()->whereKey($schedule->getKey())->lockForUpdate()->firstOrFail();
            $limit = JsonValueGuard::integer(config('pterodactyl.client_features.schedules.per_schedule_task_limit', 10));
            throw_if($schedule->tasks()->count() >= $limit, ServiceLimitExceededException::class, "Schedules may not have more than $limit tasks associated with them. Creating this task would put this schedule over the limit.");

            $next = ($schedule->tasks()->orderByDesc('sequence_id')->first()->sequence_id ?? 0) + 1;
            $requested = $attributes['sequence_id'] ?? $next;

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
            ->subject($schedule, $task)
            ->property(['name' => $schedule->name, 'action' => $task->action, 'payload' => $task->payload])
            ->log();

        return $task;
    }
}
