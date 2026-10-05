<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Illuminate\Support\Arr;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\ScheduleRules;

class StoreScheduleRequest extends ViewScheduleRequest
{
    public function permission(): string
    {
        return Permissions::ScheduleCreate->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = ScheduleRules::rules();

        return [
            'name' => $rules['name'],
            'is_active' => array_merge(['filled'], $rules['is_active']),
            'minute' => $rules['cron_minute'],
            'hour' => $rules['cron_hour'],
            'day_of_month' => $rules['cron_day_of_month'],
            'month' => $rules['cron_month'],
            'day_of_week' => $rules['cron_day_of_week'],
            'only_when_online' => $rules['only_when_online'],
        ];
    }

    /**
     * @return ScheduleCreationData
     */
    public function payload(): array
    {
        $data = parent::validated();

        return [
            'name' => JsonValueGuard::string(Arr::get($data, 'name')),
            'is_active' => array_key_exists('is_active', $data) && JsonValueGuard::boolean(Arr::get($data, 'is_active')),
            'minute' => JsonValueGuard::string(Arr::get($data, 'minute')),
            'hour' => JsonValueGuard::string(Arr::get($data, 'hour')),
            'day_of_month' => JsonValueGuard::string(Arr::get($data, 'day_of_month')),
            'month' => JsonValueGuard::string(Arr::get($data, 'month')),
            'day_of_week' => JsonValueGuard::string(Arr::get($data, 'day_of_week')),
            'only_when_online' => JsonValueGuard::boolean(Arr::get($data, 'only_when_online', false)),
        ];
    }
}
