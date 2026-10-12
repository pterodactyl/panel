<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Schedule;

interface UpdatesSchedules
{
    /**
     * The input is validated here, and cron fields that do not form a valid expression are
     * refused.
     *
     * @param  array<string, mixed>  $input  the same fields a schedule is created with
     *
     * @throws DisplayException
     */
    public function update(Schedule $schedule, array $input): Schedule;
}
