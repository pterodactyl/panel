<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class ReinstallServerService
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * ReinstallService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Repositories\Wings\DaemonServerRepository $daemonServerRepository
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepository $daemonServerRepository,
        ServerRepository $repository
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->connection = $connection;
        $this->repository = $repository;
    }

    /**
     * Reinstall a server on the remote daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Throwable
     */
    public function reinstall(Server $server)
    {
        return $this->connection->transaction(function () use ($server) {
            $updated = $this->repository->update($server->id, [
                'installed' => Server::STATUS_INSTALLING,
            ], true, true);

            $this->daemonServerRepository->setServer($server)->reinstall();

            return $updated;
        });
    }
}
