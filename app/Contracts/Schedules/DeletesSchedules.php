<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Models\Schedule;

interface DeletesSchedules
{
    public function delete(Schedule $schedule): void;
}
