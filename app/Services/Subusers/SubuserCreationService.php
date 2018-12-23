<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Subusers;

use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;

class SubuserCreationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService
     */
    protected $keyCreationService;

    /**
     * @var \Pterodactyl\Services\Subusers\PermissionCreationService
     */
    protected $permissionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $subuserRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    protected $userCreationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * SubuserCreationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                     $connection
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService    $keyCreationService
     * @param \Pterodactyl\Services\Subusers\PermissionCreationService     $permissionService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface  $serverRepository
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface $subuserRepository
     * @param \Pterodactyl\Services\Users\UserCreationService              $userCreationService
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface    $userRepository
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonKeyCreationService $keyCreationService,
        PermissionCreationService $permissionService,
        ServerRepositoryInterface $serverRepository,
        SubuserRepositoryInterface $subuserRepository,
        UserCreationService $userCreationService,
        UserRepositoryInterface $userRepository
    ) {
        $this->connection = $connection;
        $this->keyCreationService = $keyCreationService;
        $this->permissionService = $permissionService;
        $this->serverRepository = $serverRepository;
        $this->subuserRepository = $subuserRepository;
        $this->userRepository = $userRepository;
        $this->userCreationService = $userCreationService;
    }

    /**
     * @param int|\Pterodactyl\Models\Server $server
     * @param string                         $email
     * @param array                          $permissions
     * @return \Pterodactyl\Models\Subuser
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException
     * @throws \Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException
     */
    public function handle($server, $email, array $permissions)
    {
        if (! $server instanceof Server) {
            $server = $this->serverRepository->find($server);
        }

        $this->connection->beginTransaction();
        try {
            $user = $this->userRepository->findFirstWhere([['email', '=', $email]]);

            if ($server->owner_id === $user->id) {
                throw new UserIsServerOwnerException(trans('exceptions.subusers.user_is_owner'));
            }

            $subuserCount = $this->subuserRepository->findCountWhere([['user_id', '=', $user->id], ['server_id', '=', $server->id]]);
            if ($subuserCount !== 0) {
                throw new ServerSubuserExistsException(trans('exceptions.subusers.subuser_exists'));
            }
        } catch (RecordNotFoundException $exception) {
            $username = preg_replace('/([^\w\.-]+)/', '', strtok($email, '@'));
            $user = $this->userCreationService->handle([
                'email' => $email,
                'username' => $username . str_random(3),
                'name_first' => 'Server',
                'name_last' => 'Subuser',
                'root_admin' => false,
            ]);
        }

        $subuser = $this->subuserRepository->create(['user_id' => $user->id, 'server_id' => $server->id]);
        $this->keyCreationService->handle($server->id, $user->id);
        $this->permissionService->handle($subuser->id, $permissions);
        $this->connection->commit();

        return $subuser;
    }
}
