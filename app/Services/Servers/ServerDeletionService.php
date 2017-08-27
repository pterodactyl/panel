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

namespace Pterodactyl\Services\Servers;

use Illuminate\Log\Writer;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Database\DatabaseManagementService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ServerDeletionService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Pterodactyl\Services\Database\DatabaseManagementService
     */
    protected $databaseManagementService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    protected $databaseRepository;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * DeletionService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface      $databaseRepository
     * @param \Pterodactyl\Services\Database\DatabaseManagementService           $databaseManagementService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface        $repository
     * @param \Illuminate\Log\Writer                                             $writer
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepositoryInterface $daemonServerRepository,
        DatabaseRepositoryInterface $databaseRepository,
        DatabaseManagementService $databaseManagementService,
        ServerRepositoryInterface $repository,
        Writer $writer
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->connection = $connection;
        $this->databaseManagementService = $databaseManagementService;
        $this->databaseRepository = $databaseRepository;
        $this->repository = $repository;
        $this->writer = $writer;
    }

    /**
     * Set if the server should be forcibly deleted from the panel (ignoring daemon errors) or not.
     *
     * @param bool $bool
     * @return $this
     */
    public function withForce($bool = true)
    {
        $this->force = $bool;

        return $this;
    }

    /**
     * Delete a server from the panel and remove any associated databases from hosts.
     *
     * @param int|\Pterodactyl\Models\Server $server
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($server)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->withColumns(['id', 'node_id', 'uuid'])->find($server);
        }

        try {
            $this->daemonServerRepository->setNode($server->node_id)->setAccessServer($server->uuid)->delete();
        } catch (RequestException $exception) {
            $response = $exception->getResponse();

            if (is_null($response) || (! is_null($response) && $response->getStatusCode() !== 404)) {
                $this->writer->warning($exception);

                // If not forcing the deletion, throw an exception, otherwise just log it and
                // continue with server deletion process in the panel.
                if (! $this->force) {
                    throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                        'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
                    ]));
                }
            }
        }

        $this->connection->beginTransaction();
        $this->databaseRepository->withColumns('id')->findWhere([['server_id', '=', $server->id]])->each(function ($item) {
            $this->databaseManagementService->delete($item->id);
        });

        $this->repository->delete($server->id);
        $this->connection->commit();
    }
}
