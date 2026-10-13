<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Actions\Schedules\CreateSchedule;
use Pterodactyl\Enum\Permissions;

class StoreScheduleRequest extends ViewScheduleRequest
{
    public function permission(): string
    {
        return Permissions::ScheduleCreate->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CreateSchedule::rules();
    }
}
