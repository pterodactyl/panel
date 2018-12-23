<?php

namespace Pterodactyl\Jobs\Schedule;

use Exception;
use Cake\Chronos\Chronos;
use Pterodactyl\Jobs\Job;
use InvalidArgumentException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\CommandRepositoryInterface;

class RunTaskJob extends Job implements ShouldQueue
{
    use DispatchesJobs, InteractsWithQueue, SerializesModels;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\CommandRepositoryInterface
     */
    protected $commandRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface
     */
    protected $powerRepository;

    /**
     * @var int
     */
    public $schedule;

    /**
     * @var int
     */
    public $task;

    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface
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
     * @param \Pterodactyl\Contracts\Repository\Daemon\CommandRepositoryInterface $commandRepository
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService           $keyProviderService
     * @param \Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface   $powerRepository
     * @param \Pterodactyl\Contracts\Repository\TaskRepositoryInterface           $taskRepository
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\Daemon\InvalidPowerSignalException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(
        CommandRepositoryInterface $commandRepository,
        DaemonKeyProviderService $keyProviderService,
        PowerRepositoryInterface $powerRepository,
        TaskRepositoryInterface $taskRepository
    ) {
        $this->commandRepository = $commandRepository;
        $this->powerRepository = $powerRepository;
        $this->taskRepository = $taskRepository;

        $task = $this->taskRepository->getTaskForJobProcess($this->task);
        $server = $task->getRelation('server');
        $user = $server->getRelation('user');

        // Do not process a task that is not set to active.
        if (! $task->getRelation('schedule')->is_active) {
            $this->markTaskNotQueued();
            $this->markScheduleComplete();

            return;
        }

        // Perform the provided task against the daemon.
        switch ($task->action) {
            case 'power':
                $this->powerRepository->setServer($server)
                    ->setToken($keyProviderService->handle($server, $user))
                    ->sendSignal($task->payload);
                break;
            case 'command':
                $this->commandRepository->setServer($server)
                    ->setToken($keyProviderService->handle($server, $user))
                    ->send($task->payload);
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
     * @param null|\Exception $exception
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    private function markScheduleComplete()
    {
        $repository = app()->make(ScheduleRepositoryInterface::class);
        $repository->withoutFreshModel()->update($this->schedule, [
            'is_processing' => false,
            'last_run_at' => Chronos::now()->toDateTimeString(),
        ]);
    }

    /**
     * Mark a specific task as no longer being queued.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    private function markTaskNotQueued()
    {
        $repository = app()->make(TaskRepositoryInterface::class);
        $repository->update($this->task, ['is_queued' => false]);
    }
}
