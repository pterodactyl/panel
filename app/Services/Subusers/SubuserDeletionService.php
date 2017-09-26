<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Subusers;

use Illuminate\Log\Writer;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class SubuserDeletionService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * SubuserDeletionService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface       $repository
     * @param \Illuminate\Log\Writer                                             $writer
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepositoryInterface $daemonRepository,
        SubuserRepositoryInterface $repository,
        Writer $writer
    ) {
        $this->connection = $connection;
        $this->daemonRepository = $daemonRepository;
        $this->repository = $repository;
        $this->writer = $writer;
    }

    /**
     * Delete a subuser and their associated permissions from the Panel and Daemon.
     *
     * @param int $subuser
     * @return int|null
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($subuser)
    {
        $subuser = $this->repository->getWithServer($subuser);

        $this->connection->beginTransaction();
        $response = $this->repository->delete($subuser->id);

        try {
            $this->daemonRepository->setNode($subuser->server->node_id)->setAccessServer($subuser->server->uuid)
                ->setSubuserKey($subuser->daemonSecret, []);
            $this->connection->commit();

            return $response;
        } catch (RequestException $exception) {
            $this->connection->rollBack();
            $this->writer->warning($exception);

            $response = $exception->getResponse();
            throw new DisplayException(trans('exceptions.daemon_connection_failed', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }
    }
}
