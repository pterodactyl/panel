<?php

namespace Pterodactyl\Services\Schedules;

use Exception;
use Pterodactyl\Models\Schedule;
use Illuminate\Contracts\Bus\Dispatcher;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class ProcessScheduleService
{
    /**
     * ProcessScheduleService constructor.
     */
    public function __construct(private ConnectionInterface $connection, private Dispatcher $dispatcher, private DaemonServerRepository $serverRepository)
    {
    }

    /**
     * Process a schedule and push the first task onto the queue worker.
     *
     * @throws \Throwable
     */
    public function handle(Schedule $schedule, bool $now = false): void
    {
        /** @var \Pterodactyl\Models\Task $task */
        $task = $schedule->tasks()->orderBy('sequence_id')->first();

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

        $job = new RunTaskJob($task, $now);
        if ($schedule->only_when_online) {
            // Check that the server is currently in a starting or running state before executing
            // this schedule if this option has been set.
            try {
                $details = $this->serverRepository->setServer($schedule->server)->getDetails();
                $state = $details['state'] ?? 'offline';
                // If the server is stopping or offline just do nothing with this task.
                if (in_array($state, ['offline', 'stopping'])) {
                    $job->failed();

                    return;
                }
            } catch (Exception $exception) {
                if (!$exception instanceof DaemonConnectionException) {
                    // If we encountered some exception during this process that wasn't just an
                    // issue connecting to Wings run the failed sequence for a job. Otherwise we
                    // can just quietly mark the task as completed without actually running anything.
                    $job->failed($exception);
                }
                $job->failed();

                return;
            }
        }

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
