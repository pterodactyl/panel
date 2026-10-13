<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Pterodactyl\Contracts\Schedules\DeletesSchedules;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Schedule;

final class DeleteSchedule implements DeletesSchedules
{
    public function delete(Schedule $schedule): void
    {
        $schedule->delete();

        Activity::event('server:schedule.delete')->subject($schedule->server, $schedule)->property('name', $schedule->name)->log();
    }
}
