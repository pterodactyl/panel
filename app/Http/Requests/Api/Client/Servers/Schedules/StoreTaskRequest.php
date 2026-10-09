<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Models\Task;
use Pterodactyl\Models\Server;
use Illuminate\Validation\Rule;
use Pterodactyl\Models\Permission;

class StoreTaskRequest extends ViewScheduleRequest
{
    /**
     * Determine if the user is allowed to create or update a task for this schedule.
     */
    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_UPDATE;
    }

    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }

        $permission = Task::permissionForAction((string) $this->input('action'), $this->input('payload'));
        if (is_null($permission)) {
            return true;
        }

        $server = $this->route()->parameter('server');

        return $server instanceof Server && $this->user()->can($permission, $server);
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:command,power,backup',
            'payload' => [
                'required_unless:action,backup',
                'string',
                'nullable',
                Rule::when($this->input('action') === Task::ACTION_POWER, [Rule::in(Task::POWER_ACTIONS)]),
            ],
            'time_offset' => 'required|numeric|min:0|max:900',
            'sequence_id' => 'sometimes|required|numeric|min:1',
            'continue_on_failure' => 'sometimes|required|boolean',
        ];
    }
}
