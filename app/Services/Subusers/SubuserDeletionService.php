<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Subusers;

use Pterodactyl\Models\Subuser;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;

class SubuserDeletionService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService
     */
    private $keyDeletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    private $repository;

    /**
     * SubuserDeletionService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                     $connection
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService    $keyDeletionService
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface $repository
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonKeyDeletionService $keyDeletionService,
        SubuserRepositoryInterface $repository
    ) {
        $this->connection = $connection;
        $this->keyDeletionService = $keyDeletionService;
        $this->repository = $repository;
    }

    /**
     * Delete a subuser and their associated permissions from the Panel and Daemon.
     *
     * @param \Pterodactyl\Models\Subuser $subuser
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Subuser $subuser)
    {
        $this->connection->beginTransaction();
        $this->keyDeletionService->handle($subuser->server_id, $subuser->user_id);
        $this->repository->delete($subuser->id);
        $this->connection->commit();
    }
}
