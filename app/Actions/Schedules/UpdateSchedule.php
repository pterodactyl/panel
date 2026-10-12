<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Schedules\UpdatesSchedules;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Schedule;
use Throwable;

final class UpdateSchedule implements UpdatesSchedules
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws DisplayException
     */
    public function update(Schedule $schedule, array $input): Schedule
    {
        $validated = Validator::validate($input, CreateSchedule::rules());

        $schedule->fill([
            'name' => $validated['name'],
            'cron_minute' => $validated['minute'],
            'cron_hour' => $validated['hour'],
            'cron_day_of_month' => $validated['day_of_month'],
            'cron_month' => $validated['month'],
            'cron_day_of_week' => $validated['day_of_week'],
            'is_active' => $validated['is_active'] ?? false,
            'only_when_online' => $validated['only_when_online'] ?? false,
        ]);

        try {
            $schedule->next_run_at = $schedule->getNextRunDate();
        } catch (Throwable) {
            throw new DisplayException('The cron data provided does not evaluate to a valid expression.');
        }

        // A schedule switched on or off is no longer mid-run.
        if ($schedule->isDirty('is_active')) {
            $schedule->is_processing = false;
        }

        $schedule->save();

        Activity::event('server:schedule.update')
            ->subject($schedule->server, $schedule)
            ->property(['name' => $schedule->name, 'active' => $schedule->is_active])
            ->log();

        return $schedule;
    }
}
