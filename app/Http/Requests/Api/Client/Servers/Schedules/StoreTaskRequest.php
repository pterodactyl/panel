<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreTaskRequest extends ClientApiRequest
{
    /**
     * Determine if the user is allowed to create a new task for this schedule. We simply
     * check if they can modify a schedule to determine if they're able to do this. There
     * are no task specific permissions.
     *
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_SCHEDULE_UPDATE;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'action' => 'required|in:command,power',
            'payload' => 'required|string',
            'time_offset' => 'required|numeric|min:0|max:900',
            'sequence_id' => 'sometimes|required|numeric|min:1',
        ];
    }
}
