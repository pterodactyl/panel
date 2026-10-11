<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;

interface CreatesSchedules
{
    /**
     * Validates the input itself, so it can come straight from any caller.
     *
     * @param  ActionInput  $input  name, is_active, minute, hour, day_of_month, month, day_of_week and only_when_online
     *
     * @throws DisplayException
     */
    public function create(Server $server, array $input): Schedule;
}
