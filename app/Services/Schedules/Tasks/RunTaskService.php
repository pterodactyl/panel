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
