<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class TransferService
{
    /**
     * TransferService constructor.
     */
    public function __construct(
        private DaemonServerRepository $daemonServerRepository
    ) {
    }

    /**
     * Requests an archive from the daemon.
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function requestArchive(Server $server): void
    {
        $this->daemonServerRepository->setServer($server)->requestArchive();
    }
}
