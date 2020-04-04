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
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * TransferService constructor.
     *
     * @param \Pterodactyl\Repositories\Wings\DaemonServerRepository $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(
        DaemonServerRepository $daemonServerRepository,
        ServerRepositoryInterface $repository
    ) {
        $this->repository = $repository;
        $this->daemonServerRepository = $daemonServerRepository;
    }

    /**
     * Requests an archive from the daemon.
     *
     * @param int|\Pterodactyl\Models\Server $server
     *
     * @throws \Throwable
     */
    public function requestArchive(Server $server)
    {
        $this->daemonServerRepository->setServer($server)->requestArchive();
    }
}
