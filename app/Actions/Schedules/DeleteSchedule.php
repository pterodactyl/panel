<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Pterodactyl\Contracts\Schedules\DeletesSchedules;
use Pterodactyl\Models\Schedule;

final class DeleteSchedule implements DeletesSchedules
{
    public function delete(Schedule $schedule): void
    {
        $schedule->delete();
    }
}
