<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

final class ScheduleRules
{
    /**
     * Validation rules for the client schedule requests.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'server_id' => ['required', 'exists:servers,id'],
            'name' => ['required', 'string', 'max:191'],
            'cron_day_of_week' => ['required', 'string'],
            'cron_month' => ['required', 'string'],
            'cron_day_of_month' => ['required', 'string'],
            'cron_hour' => ['required', 'string'],
            'cron_minute' => ['required', 'string'],
            'is_active' => ['boolean'],
            'is_processing' => ['boolean'],
            'only_when_online' => ['boolean'],
            'last_run_at' => ['nullable', 'date'],
            'next_run_at' => ['nullable', 'date'],
        ];
    }
}
