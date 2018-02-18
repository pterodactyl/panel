<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Schedules\Tasks;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException;

class TaskCreationService
{
    const MAX_INTERVAL_TIME_SECONDS = 900;

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
        if ($delay > self::MAX_INTERVAL_TIME_SECONDS) {
            throw new TaskIntervalTooLongException(trans('exceptions.tasks.chain_interval_too_long'));
        }

        $repository = ($returnModel) ? $this->repository : $this->repository->withoutFreshModel();
        $task = $repository->create([
            'schedule_id' => $schedule,
            'sequence_id' => $data['sequence_id'],
            'action' => $data['action'],
            'payload' => $data['payload'],
            'time_offset' => $delay,
        ], false);

        return $task;
    }
}
