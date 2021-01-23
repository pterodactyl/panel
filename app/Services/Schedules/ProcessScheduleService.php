<?php

namespace Pterodactyl\Services\Schedules;

use Exception;
use Pterodactyl\Models\Schedule;
use Illuminate\Contracts\Bus\Dispatcher;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;

class ProcessScheduleService
{
    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    private $dispatcher;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * ProcessScheduleService constructor.
     */
    public function __construct(ConnectionInterface $connection, Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->connection = $connection;
    }

    /**
     * Process a schedule and push the first task onto the queue worker.
     *
     * @throws \Throwable
     */
    public function handle(Schedule $schedule, bool $now = false)
    {
        /** @var \Pterodactyl\Models\Task $task */
        $task = $schedule->tasks()->orderBy('sequence_id', 'asc')->first();

        if (is_null($task)) {
            throw new DisplayException('Cannot process schedule for task execution: no tasks are registered.');
        }

        $this->connection->transaction(function () use ($schedule, $task) {
            $schedule->forceFill([
                'is_processing' => true,
                'next_run_at' => $schedule->getNextRunDate(),
            ])->saveOrFail();

            $task->update(['is_queued' => true]);
        });

        $job = new RunTaskJob($task);

        if (!$now) {
            $this->dispatcher->dispatch($job->delay($task->time_offset));
        } else {
            // When using dispatchNow the RunTaskJob::failed() function is not called automatically
            // so we need to manually trigger it and then continue with the exception throw.
            //
            // @see https://github.com/pterodactyl/panel/issues/2550
            try {
                $this->dispatcher->dispatchNow($job);
            } catch (Exception $exception) {
                $job->failed($exception);

                throw $exception;
            }
        }
    }
}
