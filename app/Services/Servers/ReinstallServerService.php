<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class ReinstallServerService
{
    private ConnectionInterface $connection;

    private DaemonServerRepository $daemonServerRepository;

    /**
     * ReinstallService constructor.
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepository $daemonServerRepository
    ) {
        $this->connection = $connection;
        $this->daemonServerRepository = $daemonServerRepository;
    }

    /**
     * Reinstall a server on the remote daemon.
     *
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Throwable
     */
    public function handle(Server $server)
    {
        return $this->connection->transaction(function () use ($server) {
            $server->fill(['status' => Server::STATUS_INSTALLING])->save();

            $this->daemonServerRepository->setServer($server)->reinstall();

            return $server->refresh();
        });
    }
}
