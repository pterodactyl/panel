<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Illuminate\Support\Arr;
use Pterodactyl\Contracts\Schedules\CreatesSchedules;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Helpers\Utilities;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

final class CreateSchedule implements CreatesSchedules
{
    /**
     * @param  ScheduleCreationData  $data  validated schedule attributes with minute/hour/day_of_month/month/day_of_week keys
     *
     * @throws DisplayException
     */
    public function create(Server $server, array $data): Schedule
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

        $schedule = $server->schedules()->create([
            'name' => JsonValueGuard::string(Arr::get($data, 'name')),
            'cron_day_of_week' => $dayOfWeek,
            'cron_month' => $month,
            'cron_day_of_month' => $dayOfMonth,
            'cron_hour' => $hour,
            'cron_minute' => $minute,
            'is_active' => JsonValueGuard::boolean(Arr::get($data, 'is_active')),
            'only_when_online' => JsonValueGuard::boolean(Arr::get($data, 'only_when_online')),
            'next_run_at' => $nextRunAt,
        ]);

        return $schedule->refresh();
    }
}
