<?php

namespace Pterodactyl\Jobs\Schedule;

use Exception;
use Carbon\Carbon;
use Pterodactyl\Jobs\Job;
use InvalidArgumentException;
use Illuminate\Container\Container;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Pterodactyl\Repositories\Eloquent\TaskRepository;
use Pterodactyl\Services\Backups\InitiateBackupService;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class RunTaskJob extends Job implements ShouldQueue
{
    use DispatchesJobs, InteractsWithQueue, SerializesModels;

    /**
     * @var int
     */
    public $schedule;

    /**
     * @var int
     */
    public $task;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\TaskRepository
     */
    protected $taskRepository;

    /**
     * RunTaskJob constructor.
     *
     * @param int $task
     * @param int $schedule
     */
    public function __construct(int $task, int $schedule)
    {
        $this->queue = config('pterodactyl.queues.standard');
        $this->task = $task;
        $this->schedule = $schedule;
    }

    /**
     * Run the job and send actions to the daemon running the server.
     *
     * @param \Pterodactyl\Repositories\Wings\DaemonCommandRepository $commandRepository
     * @param \Pterodactyl\Services\Backups\InitiateBackupService $backupService
     * @param \Pterodactyl\Repositories\Wings\DaemonPowerRepository $powerRepository
     * @param \Pterodactyl\Repositories\Eloquent\TaskRepository $taskRepository
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Throwable
     */
    public function handle(
        DaemonCommandRepository $commandRepository,
        InitiateBackupService $backupService,
        DaemonPowerRepository $powerRepository,
        TaskRepository $taskRepository
    ) {
        $this->taskRepository = $taskRepository;

        $task = $this->taskRepository->getTaskForJobProcess($this->task);
        $server = $task->getRelation('server');

        // Do not process a task that is not set to active.
        if (! $task->getRelation('schedule')->is_active) {
            $this->markTaskNotQueued();
            $this->markScheduleComplete();

            return;
        }

        // Perform the provided task against the daemon.
        switch ($task->action) {
            case 'power':
                $powerRepository->setServer($server)->send($task->payload);
                break;
            case 'command':
                $commandRepository->setServer($server)->send($task->payload);
                break;
            case 'backup':
                $backupService->setIgnoredFiles(explode(PHP_EOL, $task->payload))->handle($server, null);
                break;
            default:
                throw new InvalidArgumentException('Cannot run a task that points to a non-existent action.');
        }

        $this->markTaskNotQueued();
        $this->queueNextTask($task->sequence_id);
    }

    /**
     * Handle a failure while sending the action to the daemon or otherwise processing the job.
     *
     * @param \Exception|null $exception
     */
    public function failed(Exception $exception = null)
    {
        $this->markTaskNotQueued();
        $this->markScheduleComplete();
    }

    /**
     * Get the next task in the schedule and queue it for running after the defined period of wait time.
     *
     * @param int $sequence
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    private function queueNextTask($sequence)
    {
        $nextTask = $this->taskRepository->getNextTask($this->schedule, $sequence);
        if (is_null($nextTask)) {
            $this->markScheduleComplete();

            return;
        }

        $this->taskRepository->update($nextTask->id, ['is_queued' => true]);
        $this->dispatch((new self($nextTask->id, $this->schedule))->delay($nextTask->time_offset));
    }

    /**
     * Marks the parent schedule as being complete.
     */
    private function markScheduleComplete()
    {
        Container::getInstance()
            ->make(ScheduleRepositoryInterface::class)
            ->withoutFreshModel()
            ->update($this->schedule, [
                'is_processing' => false,
                'last_run_at' => Carbon::now()->toDateTimeString(),
            ]);
    }

    /**
     * Mark a specific task as no longer being queued.
     */
    private function markTaskNotQueued()
    {
        Container::getInstance()
            ->make(TaskRepositoryInterface::class)
            ->update($this->task, ['is_queued' => false]);
    }
}
