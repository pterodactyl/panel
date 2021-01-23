<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

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
