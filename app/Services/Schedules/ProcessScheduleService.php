<?php

namespace Pterodactyl\Services\Schedules;

use Pterodactyl\Models\Schedule;
use Illuminate\Contracts\Bus\Dispatcher;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Illuminate\Database\ConnectionInterface;

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
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Illuminate\Contracts\Bus\Dispatcher $dispatcher
     */
    public function __construct(ConnectionInterface $connection, Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->connection = $connection;
    }

    /**
     * Process a schedule and push the first task onto the queue worker.
     *
     * @param \Pterodactyl\Models\Schedule $schedule
     * @param bool $now
     *
     * @throws \Throwable
     */
    public function handle(Schedule $schedule, bool $now = false)
    {
        /** @var \Pterodactyl\Models\Task $task */
        $task = $schedule->tasks()->where('sequence_id', 1)->firstOrFail();

        $this->connection->transaction(function () use ($schedule, $task) {
            $schedule->forceFill([
                'is_processing' => true,
                'next_run_at' => $schedule->getNextRunDate(),
            ])->saveOrFail();

            $task->update(['is_queued' => true]);
        });

        $this->dispatcher->{$now ? 'dispatchNow' : 'dispatch'}(
            (new RunTaskJob($task))->delay($task->time_offset)
        );
    }
}
