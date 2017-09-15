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

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Schedules\Tasks\TaskCreationService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ScheduleCreationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Schedules\Tasks\TaskCreationService
     */
    protected $taskCreationService;

    /**
     * ScheduleCreationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                      $connection
     * @param \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface $repository
     * @param \Pterodactyl\Services\Schedules\Tasks\TaskCreationService     $taskCreationService
     */
    public function __construct(
        ConnectionInterface $connection,
        ScheduleRepositoryInterface $repository,
        TaskCreationService $taskCreationService
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->taskCreationService = $taskCreationService;
    }

    /**
     * Create a new schedule for a specific server.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param array                          $data
     * @param array                          $tasks
     * @return \Pterodactyl\Models\Schedule
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException
     */
    public function handle($server, array $data, array $tasks = [])
    {
        Assert::true(($server instanceof Server || is_digit($server)),
            'First argument passed to handle must be numeric or instance of \Pterodactyl\Models\Server, received %s.'
        );

        $server = ($server instanceof Server) ? $server->id : $server;
        $data['server_id'] = $server;

        $this->connection->beginTransaction();
        $schedule = $this->repository->create($data);

        if (! empty($tasks)) {
            foreach ($tasks as $index => $task) {
                $this->taskCreationService->handle($schedule, [
                    'time_interval' => array_get($task, 'time_interval'),
                    'time_value' => array_get($task, 'time_value'),
                    'sequence_id' => $index + 1,
                    'action' => array_get($task, 'action'),
                    'payload' => array_get($task, 'payload'),
                ], false);
            }
        }

        $this->connection->commit();

        return $schedule;
    }
}
