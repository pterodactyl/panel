<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;

class TriggerScheduleRequest extends FormRequest
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
    public function rules()
    {
        return [];
    }
}
