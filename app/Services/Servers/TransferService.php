<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class TransferService
{

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $writer;

    /**
     * TransferService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Repositories\Wings\DaemonServerRepository $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     * @param \Psr\Log\LoggerInterface $writer
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepository $daemonServerRepository,
        ServerRepositoryInterface $repository,
        LoggerInterface $writer
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->writer = $writer;
    }

    public function handle(Server $server)
    {

    }
}
