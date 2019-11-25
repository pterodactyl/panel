<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

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
     * @param int|\Pterodactyl\Models\Server $server
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function reinstall($server)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        $this->database->beginTransaction();
        $this->repository->withoutFreshModel()->update($server->id, [
            'installed' => 0,
        ], true, true);

        try {
            $this->daemonServerRepository->setServer($server)->reinstall();
            $this->database->commit();
        } catch (RequestException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
