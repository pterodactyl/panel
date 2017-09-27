<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Schedules;

use Cron\CronExpression;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Services\Schedules\Tasks\RunTaskService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessScheduleService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Schedules\Tasks\RunTaskService
     */
    protected $runnerService;

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
     * Process a schedule and push the first task onto the queue worker.
     *
     * @param int|\Pterodactyl\Models\Schedule $schedule
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($schedule)
    {
        Assert::true(($schedule instanceof Schedule || is_digit($schedule)),
            'First argument passed to handle must be instance of \Pterodactyl\Models\Schedule or an integer, received %s.'
        );

        if (($schedule instanceof Schedule && ! $schedule->relationLoaded('tasks')) || ! $schedule instanceof Schedule) {
            $schedule = $this->repository->getScheduleWithTasks(is_digit($schedule) ? $schedule : $schedule->id);
        }

        $formattedCron = sprintf('%s %s %s * %s *',
            $schedule->cron_minute,
            $schedule->cron_hour,
            $schedule->cron_day_of_month,
            $schedule->cron_day_of_week
        );

        $this->repository->update($schedule->id, [
            'is_processing' => true,
            'next_run_at' => CronExpression::factory($formattedCron)->getNextRunDate(),
        ]);

        $task = $schedule->tasks->where('sequence_id', 1)->first();
        $this->runnerService->handle($task);
    }
}
