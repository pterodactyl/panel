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
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ReinstallServerService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $database;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * ReinstallService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                           $database
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface        $repository
     * @param \Illuminate\Log\Writer                                             $writer
     */
    public function __construct(
        ConnectionInterface $database,
        DaemonServerRepositoryInterface $daemonServerRepository,
        ServerRepositoryInterface $repository,
        Writer $writer
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->database = $database;
        $this->repository = $repository;
        $this->writer = $writer;
    }

    /**
     * @param int|\Pterodactyl\Models\Server $server
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function reinstall($server)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        $this->database->beginTransaction();
        $this->repository->withoutFresh()->update($server->id, [
            'installed' => 0,
        ]);

        try {
            $this->daemonServerRepository->setNode($server->node_id)->setAccessServer($server->uuid)->reinstall();
            $this->database->commit();
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }
    }
}
