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

namespace Pterodactyl\Services\Schedules\Tasks;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException;

class TaskCreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface
     */
    protected $repository;

    /**
     * TaskCreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\TaskRepositoryInterface $repository
     */
    public function __construct(TaskRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new task that is assigned to a schedule.
     *
     * @param int|\Pterodactyl\Models\Schedule $schedule
     * @param array                            $data
     * @param bool                             $returnModel
     * @return bool|\Pterodactyl\Models\Task
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException
     */
    public function handle($schedule, array $data, $returnModel = true)
    {
        Assert::true(($schedule instanceof Schedule || is_digit($schedule)),
            'First argument passed to handle must be numeric or instance of \Pterodactyl\Models\Schedule, received %s.'
        );

        $schedule = ($schedule instanceof Schedule) ? $schedule->id : $schedule;
        $delay = $data['time_interval'] === 'm' ? $data['time_value'] * 60 : $data['time_value'];
        if ($delay > 900) {
            throw new TaskIntervalTooLongException(trans('exceptions.tasks.chain_interval_too_long'));
        }

        $repository = ($returnModel) ? $this->repository : $this->repository->withoutFresh();
        $task = $repository->create([
            'schedule_id' => $schedule,
            'sequence_id' => $data['sequence_id'],
            'action' => $data['action'],
            'payload' => $data['payload'],
            'time_offset' => $delay,
        ]);

        return $task;
    }
}
