<?php

namespace Pterodactyl\Services\Schedules;

use Cron\CronExpression;
use Pterodactyl\Models\Schedule;
use Illuminate\Contracts\Bus\Dispatcher;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessScheduleService
{
    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    private $dispatcher;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    private $scheduleRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * ProcessScheduleService constructor.
     *
     * @param \Illuminate\Contracts\Bus\Dispatcher                          $dispatcher
     * @param \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface $scheduleRepository
     * @param \Pterodactyl\Contracts\Repository\TaskRepositoryInterface     $taskRepository
     */
    public function __construct(
        Dispatcher $dispatcher,
        ScheduleRepositoryInterface $scheduleRepository,
        TaskRepositoryInterface $taskRepository
    ) {
        $this->dispatcher = $dispatcher;
        $this->scheduleRepository = $scheduleRepository;
        $this->taskRepository = $taskRepository;
    }

    /**
     * Process a schedule and push the first task onto the queue worker.
     *
     * @param \Pterodactyl\Models\Schedule $schedule
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Schedule $schedule)
    {
        $this->scheduleRepository->loadTasks($schedule);

        /** @var \Pterodactyl\Models\Task $task */
        $task = $schedule->getRelation('tasks')->where('sequence_id', 1)->first();

        $formattedCron = sprintf('%s %s %s * %s',
            $schedule->cron_minute,
            $schedule->cron_hour,
            $schedule->cron_day_of_month,
            $schedule->cron_day_of_week
        );

        $this->scheduleRepository->update($schedule->id, [
            'is_processing' => true,
            'next_run_at' => CronExpression::factory($formattedCron)->getNextRunDate(),
        ]);

        $this->taskRepository->update($task->id, ['is_queued' => true]);

        $this->dispatcher->dispatch(
            (new RunTaskJob($task->id, $schedule->id))->delay($task->time_offset)
        );
    }
}
