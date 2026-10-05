<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Illuminate\Support\Arr;
use Pterodactyl\Contracts\Schedules\UpdatesSchedules;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Helpers\Utilities;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

final class UpdateSchedule implements UpdatesSchedules
{
    /**
     * @param  ScheduleUpdateData  $data
     *
     * @throws DisplayException
     */
    public function update(Schedule $schedule, array $data): Schedule
    {
        $minute = JsonValueGuard::string(Arr::get($data, 'minute'));
        $hour = JsonValueGuard::string(Arr::get($data, 'hour'));
        $dayOfMonth = JsonValueGuard::string(Arr::get($data, 'day_of_month'));
        $month = JsonValueGuard::string(Arr::get($data, 'month'));
        $dayOfWeek = JsonValueGuard::string(Arr::get($data, 'day_of_week'));

        try {
            $nextRunAt = Utilities::getScheduleNextRunDate($minute, $hour, $dayOfMonth, $month, $dayOfWeek);
        } catch (Throwable) {
            throw new DisplayException('The cron data provided does not evaluate to a valid expression.');
        }

        $active = JsonValueGuard::boolean(Arr::get($data, 'is_active'));

        $attributes = [
            'name' => JsonValueGuard::string(Arr::get($data, 'name')),
            'cron_day_of_week' => $dayOfWeek,
            'cron_month' => $month,
            'cron_day_of_month' => $dayOfMonth,
            'cron_hour' => $hour,
            'cron_minute' => $minute,
            'is_active' => $active,
            'only_when_online' => JsonValueGuard::boolean(Arr::get($data, 'only_when_online')),
            'next_run_at' => $nextRunAt,
        ];

        if ($schedule->is_active !== $active) {
            $attributes['is_processing'] = false;
        }

        $schedule->update($attributes);

        return $schedule->refresh();
    }
}
