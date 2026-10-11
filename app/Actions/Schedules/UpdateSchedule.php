<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Pterodactyl\Contracts\Schedules\UpdatesSchedules;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Schedule;

final class UpdateSchedule implements UpdatesSchedules
{
    /**
     * @param  ActionInput  $input
     *
     * @throws DisplayException
     */
    public function update(Schedule $schedule, array $input): Schedule
    {
        $attributes = CreateSchedule::attributes($input);

        // A schedule switched on or off is no longer mid-run.
        if ($schedule->is_active !== $attributes['is_active']) {
            $attributes['is_processing'] = false;
        }

        $schedule->update($attributes);

        Activity::event('server:schedule.update')
            ->subject($schedule->server, $schedule)
            ->property(['name' => $schedule->name, 'active' => $schedule->is_active])
            ->log();

        return $schedule;
    }
}
