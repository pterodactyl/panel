<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Schedules\CreatesSchedules;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Throwable;

final class CreateSchedule implements CreatesSchedules
{
    /** @return NormalizedValidationRules */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'is_active' => ['filled', 'boolean'],
            'minute' => ['required', 'string'],
            'hour' => ['required', 'string'],
            'day_of_month' => ['required', 'string'],
            'month' => ['required', 'string'],
            'day_of_week' => ['required', 'string'],
            'only_when_online' => ['boolean'],
        ];
    }

    public function create(Server $server, array $input): Schedule
    {
        $validated = Validator::validate($input, self::rules());

        $schedule = $server->schedules()->make([
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

        $schedule->save();

        Activity::event('server:schedule.create')
            ->subject($server, $schedule)
            ->property('name', $schedule->name)
            ->log();

        return $schedule->refresh();
    }
}
