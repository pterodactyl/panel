<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Schedules\Tasks;

use Pterodactyl\Models\Task;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;

class RunTaskService
{
    use DispatchesJobs;

    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface
     */
    protected $repository;

    /**
     * RunTaskService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\TaskRepositoryInterface $repository
     */
    public function __construct(TaskRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Push a single task onto the queue.
     *
     * @param int|\Pterodactyl\Models\Task $task
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($task)
    {
        if (! $task instanceof Task) {
            $task = $this->repository->find($task);
        }

        $this->repository->update($task->id, ['is_queued' => true]);
        $this->dispatch((new RunTaskJob($task->id, $task->schedule_id))->delay($task->time_offset));
    }
}
