<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Models\Permission;

class UpdateScheduleRequest extends StoreScheduleRequest
{
    /**
     * @return string
     */
    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_UPDATE;
    }
}
