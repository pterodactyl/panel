<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class ContainerRebuildService
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $repository;

    /**
     * ContainerRebuildService constructor.
     *
     * @param \Pterodactyl\Repositories\Wings\DaemonServerRepository $repository
     */
    public function __construct(DaemonServerRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Mark a server for rebuild on next boot cycle. This just makes an empty patch
     * request to Wings which will automatically mark the container as requiring a rebuild
     * on the next boot as a result.
     *
     * @param \Pterodactyl\Models\Server $server
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function handle(Server $server)
    {
        try {
            $this->repository->setServer($server)->update([]);
        } catch (RequestException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
