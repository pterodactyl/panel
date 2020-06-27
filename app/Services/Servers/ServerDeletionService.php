<?php

namespace Pterodactyl\Services\Servers;

use Exception;
use Psr\Log\LoggerInterface;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Eloquent\DatabaseRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class ServerDeletionService
{
    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\DatabaseRepository
     */
    private $databaseRepository;

    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    private $databaseManagementService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $writer;

    /**
     * DeletionService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Repositories\Wings\DaemonServerRepository $daemonServerRepository
     * @param \Pterodactyl\Repositories\Eloquent\DatabaseRepository $databaseRepository
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService $databaseManagementService
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     * @param \Psr\Log\LoggerInterface $writer
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepository $daemonServerRepository,
        DatabaseRepository $databaseRepository,
        DatabaseManagementService $databaseManagementService,
        ServerRepository $repository,
        LoggerInterface $writer
    ) {
        $this->connection = $connection;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->databaseRepository = $databaseRepository;
        $this->databaseManagementService = $databaseManagementService;
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
     * @param \Pterodactyl\Models\Server $server
     *
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function handle(Server $server)
    {
        try {
            $this->daemonServerRepository->setServer($server)->delete();
        } catch (DaemonConnectionException $exception) {
            if ($this->force) {
                $this->writer->warning($exception);
            } else {
                throw $exception;
            }
        }

        $this->connection->transaction(function () use ($server) {
            $this->databaseRepository->setColumns('id')->findWhere([['server_id', '=', $server->id]])->each(function ($item) {
                try {
                    $this->databaseManagementService->delete($item->id);
                } catch (Exception $exception) {
                    if ($this->force) {
                        $this->writer->warning($exception);
                    } else {
                        throw $exception;
                    }
                }
            });

            $this->repository->delete($server->id);
        });
    }
}
