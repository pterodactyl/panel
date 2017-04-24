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

namespace Pterodactyl\Jobs;

use Cron;
use Carbon;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\TaskLog;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Pterodactyl\Repositories\Daemon\PowerRepository;
use Pterodactyl\Repositories\Daemon\CommandRepository;

class SendScheduledTask extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * @var \Pterodactyl\Models\Task
     */
    protected $task;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;

        $this->task->queued = true;
        $this->task->save();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $time = Carbon::now();
        $log = new TaskLog;

        if ($this->attempts() >= 1) {
            // Just delete the job, we will attempt it again later anyways.
            $this->delete();
        }

        try {
            if ($this->task->action === 'command') {
                $repo = new CommandRepository($this->task->server, $this->task->user);
                $response = $repo->send($this->task->data);
            } elseif ($this->task->action === 'power') {
                $repo = new PowerRepository($this->task->server, $this->task->user);
                $response = $repo->do($this->task->data);
            } else {
                throw new \Exception('Task type provided was not valid.');
            }

            $log->fill([
                'task_id' => $this->task->id,
                'run_time' => $time,
                'run_status' => 0,
                'response' => $response,
            ]);
        } catch (\Exception $ex) {
            $log->fill([
                'task_id' => $this->task->id,
                'run_time' => $time,
                'run_status' => 1,
                'response' => $ex->getMessage(),
            ]);
        } finally {
            $cron = Cron::factory(sprintf('%s %s %s %s %s %s',
                $this->task->minute,
                $this->task->hour,
                $this->task->day_of_month,
                $this->task->month,
                $this->task->day_of_week,
                $this->task->year
            ));
            $this->task->fill([
                'last_run' => $time,
                'next_run' => $cron->getNextRunDate(),
                'queued' => false,
            ]);
            $this->task->save();
            $log->save();
        }
    }
}
