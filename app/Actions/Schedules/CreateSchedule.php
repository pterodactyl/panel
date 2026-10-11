<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Schedules\CreatesSchedules;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Helpers\Utilities;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Throwable;

final class CreateSchedule implements CreatesSchedules
{
    /**
     * A schedule's fields, for creating one and for updating one.
     *
     * @return NormalizedValidationRules
     */
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

    /**
     * Validates schedule input and returns the model attributes it describes.
     *
     * @param  ActionInput  $input
     * @return array{name: string, cron_minute: string, cron_hour: string, cron_day_of_month: string, cron_month: string, cron_day_of_week: string, is_active: bool, only_when_online: bool, next_run_at: CarbonImmutable}
     *
     * @throws DisplayException
     */
    public static function attributes(array $input): array
    {
        $input = Validator::make($input, self::rules())->safe();
        $cron = [
            'cron_minute' => $input->string('minute')->toString(),
            'cron_hour' => $input->string('hour')->toString(),
            'cron_day_of_month' => $input->string('day_of_month')->toString(),
            'cron_month' => $input->string('month')->toString(),
            'cron_day_of_week' => $input->string('day_of_week')->toString(),
        ];

        try {
            $nextRunAt = Utilities::getScheduleNextRunDate($cron['cron_minute'], $cron['cron_hour'], $cron['cron_day_of_month'], $cron['cron_month'], $cron['cron_day_of_week']);
        } catch (Throwable) {
            throw new DisplayException('The cron data provided does not evaluate to a valid expression.');
        }

        return [
            'name' => $input->string('name')->toString(),
            ...$cron,
            'is_active' => $input->boolean('is_active'),
            'only_when_online' => $input->boolean('only_when_online'),
            'next_run_at' => $nextRunAt,
        ];
    }

    /**
     * @param  ActionInput  $input
     *
     * @throws DisplayException
     */
    public function create(Server $server, array $input): Schedule
    {
        $schedule = $server->schedules()->create(self::attributes($input));

        Activity::event('server:schedule.create')
            ->subject($schedule)
            ->property('name', $schedule->name)
            ->log();

        return $schedule->refresh();
    }
}
