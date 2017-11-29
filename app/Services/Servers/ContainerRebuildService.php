<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Servers;

use Illuminate\Log\Writer;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ContainerRebuildService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * ContainerRebuildService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface        $repository
     * @param \Illuminate\Log\Writer                                             $writer
     */
    public function __construct(
        DaemonServerRepositoryInterface $daemonServerRepository,
        ServerRepositoryInterface $repository,
        Writer $writer
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->repository = $repository;
        $this->writer = $writer;
    }

    /**
     * Mark a server for rebuild on next boot cycle.
     *
     * @param int|\Pterodactyl\Models\Server $server
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function rebuild($server)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        try {
            $this->daemonServerRepository->setNode($server->node_id)->setAccessServer($server->uuid)->rebuild();
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }
    }
}
