<?php

namespace App\Services\Servers;

use App\Models\Server;
use GuzzleHttp\Exception\RequestException;
use App\Exceptions\Http\Connection\DaemonConnectionException;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface;

class ContainerRebuildService
{
    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ContainerRebuildService constructor.
     *
     * @param \App\Contracts\Repository\Daemon\ServerRepositoryInterface $repository
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Mark a server for rebuild on next boot cycle.
     *
     * @param \App\Models\Server $server
     *
     * @throws \App\Exceptions\Http\Connection\DaemonConnectionException
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
