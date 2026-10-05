<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Task;
use Pterodactyl\Support\JsonValueGuard;

class StoreTaskRequest extends ViewScheduleRequest
{
    /**
     * Creating or changing a task needs permission to update the schedule.
     */
    public function permission(): string
    {
        return Permissions::ScheduleUpdate->value;
    }

    /**
     * The user also needs the permission for the task's action, such as console access for
     * a command task. An unknown action or power signal is left to validation to reject.
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $permission = Task::permissionForAction($this->string('action')->toString(), $this->string('payload')->toString());

        return ! $permission instanceof Permissions || $this->user()->can($permission->value, $this->parameter('server', Server::class));
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        $payload = ['required_unless:action,backup', 'string', 'nullable'];
        if ($this->input('action') === Task::ACTION_POWER) {
            $payload[] = Rule::in(Task::POWER_ACTIONS);
        }

        return [
            'action' => ['required', 'in:command,power,backup'],
            'payload' => $payload,
            'time_offset' => ['required', 'numeric', 'min:0', 'max:900'],
            'sequence_id' => ['sometimes', 'required', 'numeric', 'min:1'],
            'continue_on_failure' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * @return TaskData
     */
    public function payload(): array
    {
        $data = parent::validated();

        $sequenceId = Arr::get($data, 'sequence_id');

        return [
            'action' => JsonValueGuard::string(Arr::get($data, 'action')),
            'payload' => JsonValueGuard::nullableScalarString(Arr::get($data, 'payload')) ?? '',
            'time_offset' => JsonValueGuard::integer(Arr::get($data, 'time_offset')),
            'sequence_id' => $sequenceId === null || $sequenceId === '' ? null : JsonValueGuard::integer($sequenceId),
            'continue_on_failure' => JsonValueGuard::boolean(Arr::get($data, 'continue_on_failure', false)),
        ];
    }
}
