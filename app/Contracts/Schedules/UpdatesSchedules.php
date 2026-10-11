<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Schedule;

interface UpdatesSchedules
{
    /**
     * Validates the input itself, so it can come straight from any caller.
     *
     * @param  ActionInput  $input  the same fields a schedule is created with
     *
     * @throws DisplayException
     */
    public function update(Schedule $schedule, array $input): Schedule;
}
