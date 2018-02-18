<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface;

class ContainerRebuildService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ContainerRebuildService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $repository
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Mark a server for rebuild on next boot cycle.
     *
     * @param \Pterodactyl\Models\Server $server
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function handle(Server $server)
    {
        try {
            $this->repository->setServer($server)->rebuild();
        } catch (RequestException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
