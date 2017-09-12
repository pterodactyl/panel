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

class TaskCreationFormRequest extends FrontendUserFormRequest
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
            'day_of_week' => 'required|string',
            'day_of_month' => 'required|string',
            'hour' => 'required|string',
            'minute' => 'required|string',
            'action' => 'required|string|in:power,command',
            'data' => 'required|string',
            'chain' => 'sometimes|array|size:4',
            'chain.time_value' => 'required_with:chain|max:5',
            'chain.time_interval' => 'required_with:chain|max:5',
            'chain.action' => 'required_with:chain|max:5',
            'chain.payload' => 'required_with:chain|max:5',
            'chain.time_value.*' => 'numeric|between:1,60',
            'chain.time_interval.*' => 'string|in:s,m',
            'chain.action.*' => 'string|in:power,command',
            'chain.payload.*' => 'string',
        ];
    }

    /**
     * Normalize the request into a format that can be used by the application.
     *
     * @return array
     */
    public function normalize()
    {
        return $this->only('name', 'day_of_week', 'day_of_month', 'hour', 'minute', 'action', 'data');
    }

    /**
     * Return the chained tasks provided in the request.
     *
     * @return array|null
     */
    public function getChainedTasks()
    {
        $restructured = [];
        foreach (array_get($this->all(), 'chain', []) as $key => $values) {
            for ($i = 0; $i < count($values); ++$i) {
                $restructured[$i][$key] = $values[$i];
            }
        }

        return empty($restructured) ? null : $restructured;
    }
}
