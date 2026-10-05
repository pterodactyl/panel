<?php

declare(strict_types=1);

namespace Pterodactyl\Jobs\Schedule;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Pterodactyl\Contracts\Backups\InitiatesBackups;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Throwable;

class RunTaskJob implements ShouldQueue
{
    use DispatchesJobs;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 30;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public int $tries = 3;

    /**
     * RunTaskJob constructor.
     */
    public function __construct(public Task $task, public bool $manualRun = false)
    {
        $this->queue = 'standard';
    }

    /**
     * Run the job and send actions to the daemon running the server.
     *
     * @throws Throwable
     */
    public function handle(
        InitiatesBackups $backupService,
    ): void {
        // Do not process a task that is not set to active, unless it's been manually triggered.
        if (! $this->task->schedule->is_active && ! $this->manualRun) {
            $this->markTaskNotQueued();
            $this->markScheduleComplete();

            return;
        }

        $server = $this->task->server;
        // If we made it to this point and the server status is not null it means the
        // server was likely suspended or marked as reinstalling after the schedule
        // was queued up. Just end the task right now - this should be a very rare
        // condition.
        if (($server->status) !== null) {
            $this->failed();

            return;
        }

        // Perform the provided task against the daemon.
        try {
            match ($this->task->action) {
                Task::ACTION_POWER => Daemon::server($server)->power($this->task->payload),
                Task::ACTION_COMMAND => Daemon::server($server)->commands($this->task->payload),
                Task::ACTION_BACKUP => $backupService->setIgnoredFiles(explode(PHP_EOL, $this->task->payload))->initiate($server, null, true),
                default => throw new InvalidArgumentException('Invalid task action provided: '.$this->task->action),
            };
        } catch (Throwable $throwable) {
            // If this isn't a DaemonConnectionException on a task that allows for failures
            // throw the exception back up the chain so that the task is stopped.
            throw_if(! $this->task->continue_on_failure || ! $throwable instanceof DaemonConnectionException, $throwable);
        }

        $this->markTaskNotQueued();
        $this->queueNextTask();
    }

    /**
     * Handle a failure while sending the action to the daemon or otherwise processing the job.
     */
    public function failed(?Throwable $exception = null): void
    {
        $this->task->getConnection()->transaction(function (): void {
            $schedule = Schedule::query()->without('tasks')->lockForUpdate()->findOrFail($this->task->schedule_id);
            $schedule->tasks()->where('is_queued', true)->update(['is_queued' => false]);
            $schedule->forceFill(['is_processing' => false, 'last_run_at' => CarbonImmutable::now()])->saveOrFail();
        });
    }

    /**
     * Get the next task in the schedule and queue it for running after the defined period of wait time.
     */
    private function queueNextTask(): void
    {
        // SAFETY: this query is rooted in the Task model and first() therefore returns Task|null.
        /** @var Task|null $nextTask */
        $nextTask = Task::query()->where('schedule_id', $this->task->schedule_id)
            ->orderBy('sequence_id', 'asc')
            ->where('sequence_id', '>', $this->task->sequence_id)
            ->first();

        if (($nextTask) === null) {
            $this->markScheduleComplete();

            return;
        }

        $nextTask->update(['is_queued' => true]);

        try {
            $this->dispatch((new self($nextTask, $this->manualRun))->delay($nextTask->time_offset));
        } catch (Throwable $throwable) {
            rescue(fn () => $this->failed($throwable));

            if ($this->job !== null) {
                $this->fail($throwable);

                return;
            }

            throw $throwable;
        }
    }

    /**
     * Marks the parent schedule as being complete.
     */
    private function markScheduleComplete(): void
    {
        $this->task->schedule()->update([
            'is_processing' => false,
            'last_run_at' => CarbonImmutable::now()->toDateTimeString(),
        ]);
    }

    /**
     * Mark a specific task as no longer being queued.
     */
    private function markTaskNotQueued(): void
    {
        $this->task->update(['is_queued' => false]);
    }
}
