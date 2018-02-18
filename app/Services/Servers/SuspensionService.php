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

class SuspensionService
{
    const ACTION_SUSPEND = 'suspend';
    const ACTION_UNSUSPEND = 'unsuspend';

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
     * SuspensionService constructor.
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
     * Suspends a server on the system.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param string                         $action
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function toggle($server, $action = self::ACTION_SUSPEND)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        if (! in_array($action, [self::ACTION_SUSPEND, self::ACTION_UNSUSPEND])) {
            throw new \InvalidArgumentException(sprintf(
                'Action must be either ' . self::ACTION_SUSPEND . ' or ' . self::ACTION_UNSUSPEND . ', %s passed.',
                $action
            ));
        }

        if (
            $action === self::ACTION_SUSPEND && $server->suspended ||
            $action === self::ACTION_UNSUSPEND && ! $server->suspended
        ) {
            return true;
        }

        $this->database->beginTransaction();
        $this->repository->withoutFreshModel()->update($server->id, [
            'suspended' => $action === self::ACTION_SUSPEND,
        ]);

        try {
            $this->daemonServerRepository->setServer($server)->$action();
            $this->database->commit();

            return true;
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }
    }
}
