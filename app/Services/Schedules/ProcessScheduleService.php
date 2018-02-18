<?php

namespace Pterodactyl\Services\Schedules;

use Carbon\Carbon;
use Cron\CronExpression;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Services\Schedules\Tasks\RunTaskService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessScheduleService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Schedules\Tasks\RunTaskService
     */
    private $runnerService;

    /**
     * @var \Carbon\Carbon|null
     */
    private $runTimeOverride;

    /**
     * ProcessScheduleService constructor.
     *
     * @param \Pterodactyl\Services\Schedules\Tasks\RunTaskService          $runnerService
     * @param \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface $repository
     */
    public function __construct(RunTaskService $runnerService, ScheduleRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->runnerService = $runnerService;
    }

    /**
     * Set the time that this schedule should be run at. This will override the time
     * defined on the schedule itself. Useful for triggering one-off task runs.
     *
     * @param \Carbon\Carbon $time
     * @return $this
     */
    public function setRunTimeOverride(Carbon $time)
    {
        $this->runTimeOverride = $time;

        return $this;
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
        $this->repository->loadTasks($schedule);

        $formattedCron = sprintf('%s %s %s * %s *',
            $schedule->cron_minute,
            $schedule->cron_hour,
            $schedule->cron_day_of_month,
            $schedule->cron_day_of_week
        );

        $this->repository->update($schedule->id, [
            'is_processing' => true,
            'next_run_at' => $this->getRunAtTime($formattedCron),
        ]);

        $task = $schedule->getRelation('tasks')->where('sequence_id', 1)->first();
        $this->runnerService->handle($task);
    }

    /**
     * Get the timestamp to store in the database as the next_run time for a schedule.
     *
     * @param string $formatted
     * @return \DateTime|string
     */
    private function getRunAtTime(string $formatted)
    {
        if ($this->runTimeOverride instanceof Carbon) {
            return $this->runTimeOverride->toDateTimeString();
        }

        return CronExpression::factory($formatted)->getNextRunDate();
    }
}
