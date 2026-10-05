<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Schedules;

use Pterodactyl\Models\Schedule;
use Throwable;

interface ProcessesSchedules
{
    /**
     * Process a schedule and push the first task onto the queue worker.
     *
     * @throws Throwable
     */
    public function process(Schedule $schedule, bool $now = false): void;
}
