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
use Illuminate\Contracts\Bus\Dispatcher;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;

class RunTaskService
{
    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $dispatcher;

    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface
     */
    protected $repository;

    /**
     * RunTaskService constructor.
     *
     * @param \Illuminate\Contracts\Bus\Dispatcher                      $dispatcher
     * @param \Pterodactyl\Contracts\Repository\TaskRepositoryInterface $repository
     */
    public function __construct(
        Dispatcher $dispatcher,
        TaskRepositoryInterface $repository
    ) {
        $this->dispatcher = $dispatcher;
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
        $this->dispatcher->dispatch((new RunTaskJob($task->id, $task->schedule_id))->delay($task->time_offset));
    }
}
