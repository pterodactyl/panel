<?php

namespace Pterodactyl\Jobs;

use Pterodactyl\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use DB;
use Carbon;
use Pterodactyl\Models;
use Pterodactyl\Repositories\Daemon\CommandRepository;

class SendScheduledTask extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $server;

    protected $task;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Models\Server $server, Models\Task $task)
    {
        $this->server = $server;
        $this->task = $task;

        $task->queued = 1;
        $task->save();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $time = Carbon::now();
        try {
            if ($this->task->action === 'command') {
                $repo = new CommandRepository($this->server);
                $response = $repo->send($this->task->data);
            }

            $this->task->fill([
                'last_run' => $time,
                'next_run' => $time->addMonths($this->task->month)->addWeeks($this->task->week)->addDays($this->task->day)->addHours($this->task->hour)->addMinutes($this->task->minute)->addSeconds($this->task->second),
                'queued' => 0
            ]);
            $this->task->save();
        } catch (\Exception $ex) {
            $wasError = true;
            $response = $ex->getMessage();
            throw $ex;
        } finally {
            $log = new Models\TaskLog;
            $log->fill([
                'task_id' => $this->task->id,
                'run_time' => $time,
                'run_status' => (int) isset($wasError),
                'response' => $response
            ]);
            $log->save();
        }
    }
}
