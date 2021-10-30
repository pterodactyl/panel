<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class TransferService
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * TransferService constructor.
     */
    public function __construct(DaemonServerRepository $daemonServerRepository)
    {
        $this->daemonServerRepository = $daemonServerRepository;
    }

    /**
     * Requests an archive from the daemon.
     *
     * @throws \Throwable
     */
    public function requestArchive(Server $server)
    {
        $this->daemonServerRepository->setServer($server)->requestArchive();
    }
}
