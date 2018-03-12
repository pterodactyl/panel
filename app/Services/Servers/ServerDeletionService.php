<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Servers;

use Illuminate\Log\Writer;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
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
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
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
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService          $databaseManagementService
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
     */
    public function handle($server)
    {
        try {
            $this->daemonServerRepository->setServer($server)->delete();
        } catch (RequestException $exception) {
            $response = $exception->getResponse();

            if (is_null($response) || (! is_null($response) && $response->getStatusCode() !== 404)) {
                // If not forcing the deletion, throw an exception, otherwise just log it and
                // continue with server deletion process in the panel.
                if (! $this->force) {
                    throw new DaemonConnectionException($exception);
                } else {
                    $this->writer->warning($exception);
                }
            }
        }

        $this->connection->beginTransaction();
        $this->databaseRepository->setColumns('id')->findWhere([['server_id', '=', $server->id]])->each(function ($item) {
            $this->databaseManagementService->delete($item->id);
        });

        $this->repository->delete($server->id);
        $this->connection->commit();
    }
}
