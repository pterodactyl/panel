<?php

namespace Pterodactyl\Jobs\Schedule;

use Exception;
use Pterodactyl\Jobs\Job;
use Carbon\CarbonImmutable;
use Pterodactyl\Models\Task;
use InvalidArgumentException;
use Pterodactyl\Models\Schedule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Pterodactyl\Services\Backups\InitiateBackupService;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;

class RunTaskJob extends Job implements ShouldQueue
{
    use DispatchesJobs;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * @var \Pterodactyl\Models\Task
     */
    public $task;

    /**
     * RunTaskJob constructor.
     */
    public function __construct(Task $task)
    {
        $this->queue = config('pterodactyl.queues.standard');
        $this->task = $task;
    }

    /**
     * Run the job and send actions to the daemon running the server.
     *
     * @throws \Throwable
     */
    public function handle(
        DaemonCommandRepository $commandRepository,
        InitiateBackupService $backupService,
        DaemonPowerRepository $powerRepository
    ) {
        // Do not process a task that is not set to active.
        if (!$this->task->schedule->is_active) {
            $this->markTaskNotQueued();
            $this->markScheduleComplete();

            return;
        }

        $server = $this->task->server;
        // Perform the provided task against the daemon.
        switch ($this->task->action) {
            case 'power':
                $powerRepository->setServer($server)->send($this->task->payload);
                break;
            case 'command':
                $commandRepository->setServer($server)->send($this->task->payload);
                break;
            case 'backup':
                $backupService->setIgnoredFiles(explode(PHP_EOL, $this->task->payload))->handle($server, null, true);
                break;
            default:
                throw new InvalidArgumentException('Cannot run a task that points to a non-existent action.');
        }

        $this->markTaskNotQueued();
        $this->queueNextTask();
    }

    /**
     * Handle a failure while sending the action to the daemon or otherwise processing the job.
     */
    public function failed(Exception $exception = null)
    {
        $this->markTaskNotQueued();
        $this->markScheduleComplete();
    }

    /**
     * Get the next task in the schedule and queue it for running after the defined period of wait time.
     */
    private function queueNextTask()
    {
        /** @var \Pterodactyl\Models\Task|null $nextTask */
        $nextTask = Task::query()->where('schedule_id', $this->task->schedule_id)
            ->where('sequence_id', $this->task->sequence_id + 1)
            ->first();

        if (is_null($nextTask)) {
            $this->markScheduleComplete();

            return;
        }

        $nextTask->update(['is_queued' => true]);

        $this->dispatch((new self($nextTask))->delay($nextTask->time_offset));
    }

    /**
     * Marks the parent schedule as being complete.
     */
    private function markScheduleComplete()
    {
        $this->task->schedule()->update([
            'is_processing' => false,
            'last_run_at' => CarbonImmutable::now()->toDateTimeString(),
        ]);
    }

    /**
     * Mark a specific task as no longer being queued.
     */
    private function markTaskNotQueued()
    {
        $this->task->update(['is_queued' => false]);
    }
}
