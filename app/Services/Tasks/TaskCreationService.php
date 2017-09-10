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

namespace Pterodactyl\Services\Tasks;

use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class TaskCreationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * TaskCreationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                    $connection
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $serverRepository
     * @param \Pterodactyl\Contracts\Repository\TaskRepositoryInterface   $repository
     */
    public function __construct(
        ConnectionInterface $connection,
        ServerRepositoryInterface $serverRepository,
        TaskRepositoryInterface $repository
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
    }

    /**
     * @param int|\Pterodactyl\Models\Server $server
     * @param array                          $data
     * @param array|null                     $chain
     * @return \Pterodactyl\Models\Task
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($server, array $data, array $chain = null)
    {
        if (! $server instanceof Server) {
            $server = $this->serverRepository->find($server);
        }

        $this->connection->beginTransaction();

        $data['server_id'] = $server->id;
        $task = $this->repository->create($data);

        if (is_array($chain)) {
            foreach ($chain as $index => $values) {
                if ($values['time_interval'] === 'm' && $values['time_value'] > 15) {
                    throw new \Exception('I should fix this.');
                }

                $delay = $values['time_interval'] === 'm' ? $values['time_value'] * 60 : $values['time_value'];
                $this->repository->withoutFresh()->create([
                    'parent_task_id' => $task->id,
                    'chain_order' => $index + 1,
                    'server_id' => $server->id,
                    'action' => $values['action'],
                    'data' => $values['payload'],
                    'chain_delay' => $delay,
                ]);
            }
        }
        $this->connection->commit();

        return $task;
    }
}
