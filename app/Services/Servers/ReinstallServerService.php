<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Servers;

use App\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Exceptions\Http\Connection\DaemonConnectionException;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ReinstallServerService
{
    /**
     * @var \App\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $database;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * ReinstallService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                           $database
     * @param \App\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonServerRepository
     * @param \App\Contracts\Repository\ServerRepositoryInterface        $repository
     */
    public function __construct(
        ConnectionInterface $database,
        DaemonServerRepositoryInterface $daemonServerRepository,
        ServerRepositoryInterface $repository
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->database = $database;
        $this->repository = $repository;
    }

    /**
     * @param int|\App\Models\Server $server
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
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
