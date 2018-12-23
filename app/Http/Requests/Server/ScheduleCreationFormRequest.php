<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Server;

class ScheduleCreationFormRequest extends ServerFormRequest
{
    /**
     * Permission to validate this request against.
     *
     * @return string
     */
    protected function permission(): string
    {
        if ($this->method() === 'PATCH') {
            return 'edit-schedule';
        }

        return 'create-schedule';
    }

    /**
     * Validation rules to apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'cron_day_of_week' => 'required|string',
            'cron_day_of_month' => 'required|string',
            'cron_hour' => 'required|string',
            'cron_minute' => 'required|string',
            'tasks' => 'sometimes|array|size:4',
            'tasks.time_value' => 'required_with:tasks|max:5',
            'tasks.time_interval' => 'required_with:tasks|max:5',
            'tasks.action' => 'required_with:tasks|max:5',
            'tasks.payload' => 'required_with:tasks|max:5',
            'tasks.time_value.*' => 'numeric|between:0,59',
            'tasks.time_interval.*' => 'string|in:s,m',
            'tasks.action.*' => 'string|in:power,command',
            'tasks.payload.*' => 'string',
        ];
    }

    /**
     * Normalize the request into a format that can be used by the application.
     *
     * @return array
     */
    public function normalize()
    {
        return $this->only('name', 'cron_day_of_week', 'cron_day_of_month', 'cron_hour', 'cron_minute');
    }

    /**
     * Return the tasks provided in the request that are associated with this schedule.
     *
     * @return array|null
     */
    public function getTasks()
    {
        $restructured = [];
        foreach (array_get($this->all(), 'tasks', []) as $key => $values) {
            for ($i = 0; $i < count($values); $i++) {
                $restructured[$i][$key] = $values[$i];
            }
        }

        return empty($restructured) ? null : $restructured;
    }
}
