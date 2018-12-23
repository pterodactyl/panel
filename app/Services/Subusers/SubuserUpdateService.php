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
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Contracts\Repository\PermissionRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class SubuserUpdateService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonRepository;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * @var \Pterodactyl\Contracts\Repository\PermissionRepositoryInterface
     */
    private $permissionRepository;

    /**
     * @var \Pterodactyl\Services\Subusers\PermissionCreationService
     */
    private $permissionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    private $repository;

    /**
     * SubuserUpdateService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService          $keyProviderService
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     * @param \Pterodactyl\Services\Subusers\PermissionCreationService           $permissionService
     * @param \Pterodactyl\Contracts\Repository\PermissionRepositoryInterface    $permissionRepository
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface       $repository
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
     * @param \Pterodactyl\Models\Subuser $subuser
     * @param array                       $permissions
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
