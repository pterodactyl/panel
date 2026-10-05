<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;

interface CreatesSchedules
{
    /**
     * @param  ScheduleCreationData  $data  validated schedule attributes with minute/hour/day_of_month/month/day_of_week keys
     *
     * @throws DisplayException
     */
    public function create(Server $server, array $data): Schedule;
}
