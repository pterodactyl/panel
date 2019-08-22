<?php

namespace App\Services\Schedules;

use App\Models\Schedule;
use Cron\CronExpression;
use App\Jobs\Schedule\RunTaskJob;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Contracts\Repository\TaskRepositoryInterface;
use App\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessScheduleService
{
    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    private $dispatcher;

    /**
     * @var \App\Contracts\Repository\ScheduleRepositoryInterface
     */
    private $scheduleRepository;

    /**
     * @var \App\Contracts\Repository\TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * ProcessScheduleService constructor.
     *
     * @param \Illuminate\Contracts\Bus\Dispatcher                          $dispatcher
     * @param \App\Contracts\Repository\ScheduleRepositoryInterface $scheduleRepository
     * @param \App\Contracts\Repository\TaskRepositoryInterface     $taskRepository
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
     * @param \App\Models\Schedule $schedule
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Schedule $schedule)
    {
        $this->scheduleRepository->loadTasks($schedule);

        /** @var \App\Models\Task $task */
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
