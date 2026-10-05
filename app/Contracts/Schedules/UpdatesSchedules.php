<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Schedule;

interface UpdatesSchedules
{
    /**
     * @param  ScheduleUpdateData  $data
     *
     * @throws DisplayException
     */
    public function update(Schedule $schedule, array $data): Schedule;
}
