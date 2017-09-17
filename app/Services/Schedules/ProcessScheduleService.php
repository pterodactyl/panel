<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Services\Schedules;

use Cron\CronExpression;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Services\Schedules\Tasks\RunTaskService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessScheduleService
{
    protected $repository;

    protected $runnerService;

    public function __construct(
        RunTaskService $runnerService,
        ScheduleRepositoryInterface $repository
    ) {
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
