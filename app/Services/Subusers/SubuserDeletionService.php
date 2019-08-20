<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Subusers;

use App\Models\Subuser;
use Illuminate\Database\ConnectionInterface;
use App\Services\DaemonKeys\DaemonKeyDeletionService;
use App\Contracts\Repository\SubuserRepositoryInterface;

class SubuserDeletionService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \App\Services\DaemonKeys\DaemonKeyDeletionService
     */
    private $keyDeletionService;

    /**
     * @var \App\Contracts\Repository\SubuserRepositoryInterface
     */
    private $repository;

    /**
     * SubuserDeletionService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                     $connection
     * @param \App\Services\DaemonKeys\DaemonKeyDeletionService    $keyDeletionService
     * @param \App\Contracts\Repository\SubuserRepositoryInterface $repository
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
     * @param \App\Models\Subuser $subuser
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Subuser $subuser)
    {
        $this->connection->beginTransaction();
        $this->keyDeletionService->handle($subuser->server_id, $subuser->user_id);
        $this->repository->delete($subuser->id);
        $this->connection->commit();
    }
}
