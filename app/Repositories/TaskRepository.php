<?php
/**
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

namespace Pterodactyl\Repositories;

use Cron;
use Validator;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class TaskRepository
{
    /**
     * The default values to use for new tasks.
     *
     * @var array
     */
    protected $defaults = [
        'year' => '*',
        'day_of_week' => '*',
        'month' => '*',
        'day_of_month' => '*',
        'hour' => '*',
        'minute' => '*/30',
    ];

    /**
     * Task action types.
     *
     * @var array
     */
    protected $actions = [
        'command',
        'power',
    ];

    /**
     * Deletes a given task.
     *
     * @param  int      $id
     * @return bool
     */
    public function delete($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
    }

    /**
     * Toggles a task active or inactive.
     *
     * @param  int  $id
     * @return bool
     */
    public function toggle($id)
    {
        $task = Task::findOrFail($id);

        $task->active = ! $task->active;
        $task->queued = false;
        $task->save();

        return $task->active;
    }

    /**
     * Create a new scheduled task for a given server.
     *
     * @param  int    $server
     * @param  int    $user
     * @param  array  $data
     * @return \Pterodactyl\Models\Task
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function create($server, $user, $data)
    {
        $server = Server::findOrFail($server);
        $user = User::findOrFail($user);

        $validator = Validator::make($data, [
            'action' => 'string|required',
            'data' => 'string|required',
            'year' => 'string|sometimes',
            'day_of_week' => 'string|sometimes',
            'month' => 'string|sometimes',
            'day_of_month' => 'string|sometimes',
            'hour' => 'string|sometimes',
            'minute' => 'string|sometimes',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        if (! in_array($data['action'], $this->actions)) {
            throw new DisplayException('The action provided is not valid.');
        }

        $cron = $this->defaults;
        foreach ($this->defaults as $setting => $value) {
            if (array_key_exists($setting, $data) && ! is_null($data[$setting]) && $data[$setting] !== '') {
                $cron[$setting] = $data[$setting];
            }
        }

        // Check that is this a valid Cron Entry
        try {
            $buildCron = Cron::factory(sprintf('%s %s %s %s %s %s',
                $cron['minute'],
                $cron['hour'],
                $cron['day_of_month'],
                $cron['month'],
                $cron['day_of_week'],
                $cron['year']
            ));
        } catch (\Exception $ex) {
            throw $ex;
        }

        return Task::create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'active' => 1,
            'action' => $data['action'],
            'data' => $data['data'],
            'queued' => 0,
            'year' => $cron['year'],
            'day_of_week' => $cron['day_of_week'],
            'month' => $cron['month'],
            'day_of_month' => $cron['day_of_month'],
            'hour' => $cron['hour'],
            'minute' => $cron['minute'],
            'last_run' => null,
            'next_run' => $buildCron->getNextRunDate(),
        ]);
    }
}
