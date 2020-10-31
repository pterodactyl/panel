<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class TriggerScheduleRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_UPDATE;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [];
    }
}
