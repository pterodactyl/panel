<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Jobs;

use Cron;
use Carbon;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\TaskLog;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Pterodactyl\Repositories\old_Daemon\PowerRepository;
use Pterodactyl\Repositories\old_Daemon\CommandRepository;

class SendScheduledTask extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * @var \Pterodactyl\Models\Task
     */
    protected $task;

    /**
     * Create a new job instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;

        $this->task->queued = true;
        $this->task->save();
    }

    /**
     * Execute the job.
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
            $cron = Cron::factory(sprintf(
                '%s %s %s %s %s %s',
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
