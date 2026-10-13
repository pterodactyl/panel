<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Models\Schedule;
use Throwable;

interface ProcessesSchedules
{
    /**
     * Push the schedule's first task onto the queue worker.
     *
     * @throws Throwable
     */
    public function process(Schedule $schedule, bool $now = false): void;
}
