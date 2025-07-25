<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Models\Permission;

class StoreTaskRequest extends ViewScheduleRequest
{
    /**
     * Determine if the user is allowed to create a new task for this schedule. We simply
     * check if they can modify a schedule to determine if they're able to do this. There
     * are no task specific permissions.
     */
    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_UPDATE;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:command,power,backup',
            'payload' => 'required_unless:action,backup|string|nullable',
            'time_offset' => 'required|numeric|min:0|max:900',
            'sequence_id' => 'sometimes|required|numeric|min:1',
            'continue_on_failure' => 'sometimes|required|boolean',
        ];
    }
}
