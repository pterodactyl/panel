<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Subusers;

use App\Models\Server;
use Illuminate\Support\Str;
use App\Services\Users\UserCreationService;
use Illuminate\Database\ConnectionInterface;
use App\Contracts\Repository\UserRepositoryInterface;
use App\Services\DaemonKeys\DaemonKeyCreationService;
use App\Exceptions\Repository\RecordNotFoundException;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Contracts\Repository\SubuserRepositoryInterface;
use App\Exceptions\Service\Subuser\UserIsServerOwnerException;
use App\Exceptions\Service\Subuser\ServerSubuserExistsException;

class SubuserCreationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \App\Services\DaemonKeys\DaemonKeyCreationService
     */
    protected $keyCreationService;

    /**
     * @var \App\Services\Subusers\PermissionCreationService
     */
    protected $permissionService;

    /**
     * @var \App\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $subuserRepository;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \App\Services\Users\UserCreationService
     */
    protected $userCreationService;

    /**
     * @var \App\Contracts\Repository\UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * SubuserCreationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                     $connection
     * @param \App\Services\DaemonKeys\DaemonKeyCreationService    $keyCreationService
     * @param \App\Services\Subusers\PermissionCreationService     $permissionService
     * @param \App\Contracts\Repository\ServerRepositoryInterface  $serverRepository
     * @param \App\Contracts\Repository\SubuserRepositoryInterface $subuserRepository
     * @param \App\Services\Users\UserCreationService              $userCreationService
     * @param \App\Contracts\Repository\UserRepositoryInterface    $userRepository
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
     * @param int|\App\Models\Server $server
     * @param string                         $email
     * @param array                          $permissions
     * @return \App\Models\Subuser
     *
     * @throws \Exception
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Subuser\ServerSubuserExistsException
     * @throws \App\Exceptions\Service\Subuser\UserIsServerOwnerException
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
                'username' => $username . Str::random(3),
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
