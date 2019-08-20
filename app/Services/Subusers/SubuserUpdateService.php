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
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use App\Services\DaemonKeys\DaemonKeyProviderService;
use App\Contracts\Repository\SubuserRepositoryInterface;
use App\Contracts\Repository\PermissionRepositoryInterface;
use App\Exceptions\Http\Connection\DaemonConnectionException;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class SubuserUpdateService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \App\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonRepository;

    /**
     * @var \App\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * @var \App\Contracts\Repository\PermissionRepositoryInterface
     */
    private $permissionRepository;

    /**
     * @var \App\Services\Subusers\PermissionCreationService
     */
    private $permissionService;

    /**
     * @var \App\Contracts\Repository\SubuserRepositoryInterface
     */
    private $repository;

    /**
     * SubuserUpdateService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \App\Services\DaemonKeys\DaemonKeyProviderService          $keyProviderService
     * @param \App\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     * @param \App\Services\Subusers\PermissionCreationService           $permissionService
     * @param \App\Contracts\Repository\PermissionRepositoryInterface    $permissionRepository
     * @param \App\Contracts\Repository\SubuserRepositoryInterface       $repository
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonKeyProviderService $keyProviderService,
        DaemonServerRepositoryInterface $daemonRepository,
        PermissionCreationService $permissionService,
        PermissionRepositoryInterface $permissionRepository,
        SubuserRepositoryInterface $repository
    ) {
        $this->connection = $connection;
        $this->daemonRepository = $daemonRepository;
        $this->keyProviderService = $keyProviderService;
        $this->permissionRepository = $permissionRepository;
        $this->permissionService = $permissionService;
        $this->repository = $repository;
    }

    /**
     * Update permissions for a given subuser.
     *
     * @param \App\Models\Subuser $subuser
     * @param array                       $permissions
     *
     * @throws \App\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Subuser $subuser, array $permissions)
    {
        $subuser = $this->repository->loadServerAndUserRelations($subuser);

        $this->connection->beginTransaction();
        $this->permissionRepository->deleteWhere([['subuser_id', '=', $subuser->id]]);
        $this->permissionService->handle($subuser->id, $permissions);

        try {
            $token = $this->keyProviderService->handle($subuser->getRelation('server'), $subuser->getRelation('user'), false);
            $this->daemonRepository->setServer($subuser->getRelation('server'))->revokeAccessKey($token);
        } catch (RequestException $exception) {
            $this->connection->rollBack();
            throw new DaemonConnectionException($exception);
        }

        $this->connection->commit();
    }
}
