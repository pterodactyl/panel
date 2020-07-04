<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Models\Permission;

class StoreScheduleRequest extends ViewScheduleRequest
{
    /**
     * @return string
     */
    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_CREATE;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:1',
            'is_active' => 'filled|boolean',
            'minute' => 'required|string',
            'hour' => 'required|string',
            'day_of_month' => 'required|string',
            'day_of_week' => 'required|string',
        ];
    }
}
