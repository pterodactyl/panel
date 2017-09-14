<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Requests\Server;

use Pterodactyl\Http\Requests\FrontendUserFormRequest;

class ScheduleCreationFormRequest extends FrontendUserFormRequest
{
    /**
     * Validation rules to apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'string|max:255',
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
            for ($i = 0; $i < count($values); ++$i) {
                $restructured[$i][$key] = $values[$i];
            }
        }

        return empty($restructured) ? null : $restructured;
    }
}
