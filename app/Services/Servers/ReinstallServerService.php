<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class ReinstallServerService
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $database;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ReinstallService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $database
     * @param \Pterodactyl\Repositories\Wings\DaemonServerRepository $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(
        ConnectionInterface $database,
        DaemonServerRepository $daemonServerRepository,
        ServerRepositoryInterface $repository
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->database = $database;
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
        $this->database->transaction(function () use ($server) {
            $this->repository->withoutFreshModel()->update($server->id, [
                'installed' => Server::STATUS_INSTALLING,
            ]);

            $this->daemonServerRepository->setServer($server)->reinstall();
        });

        return $server->refresh();
    }
}
