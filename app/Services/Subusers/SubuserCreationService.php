<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Services\Subusers;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\Writer;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\PermissionRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;
use Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Pterodactyl\Models\Permission;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Users\CreationService;

class SubuserCreationService
{
    const CORE_DAEMON_PERMISSIONS = [
        's:get',
        's:console',
    ];

    const DAEMON_SECRET_BYTES = 18;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\PermissionRepositoryInterface
     */
    protected $permissionRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $subuserRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Users\CreationService
     */
    protected $userCreationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    public function __construct(
        ConnectionInterface $connection,
        CreationService $userCreationService,
        DaemonServerRepositoryInterface $daemonRepository,
        PermissionRepositoryInterface $permissionRepository,
        ServerRepositoryInterface $serverRepository,
        SubuserRepositoryInterface $subuserRepository,
        UserRepositoryInterface $userRepository,
        Writer $writer
    ) {
        $this->connection = $connection;
        $this->daemonRepository = $daemonRepository;
        $this->permissionRepository = $permissionRepository;
        $this->subuserRepository = $subuserRepository;
        $this->serverRepository = $serverRepository;
        $this->userRepository = $userRepository;
        $this->userCreationService = $userCreationService;
        $this->writer = $writer;
    }

    /**
     * @param int|\Pterodactyl\Models\Server $server
     * @param string                         $email
     * @param array                          $permissions
     * @return \Pterodactyl\Models\Subuser
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
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

        $user = $this->userRepository->findWhere([['email', '=', $email]]);
        if (is_null($user)) {
            $user = $this->userCreationService->handle([
                'email' => $email,
                'username' => substr(strtok($email, '@'), 0, 8),
                'name_first' => 'Server',
                'name_last' => 'Subuser',
                'root_admin' => false,
            ]);
        } else {
            if ($server->owner_id === $user->id) {
                throw new UserIsServerOwnerException(trans('admin/exceptions.subusers.user_is_owner'));
            }

            $subuserCount = $this->subuserRepository->findCountWhere([['user_id', '=', $user->id], ['server_id', '=', $server->id]]);
            if ($subuserCount !== 0) {
                throw new ServerSubuserExistsException(trans('admin/exceptions.subusers.subuser_exists'));
            }
        }

        $this->connection->beginTransaction();
        $subuser = $this->subuserRepository->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'daemonSecret' => bin2hex(random_bytes(self::DAEMON_SECRET_BYTES)),
        ]);

        $permissionMappings = Permission::getPermissions(true);
        $daemonPermissions = self::CORE_DAEMON_PERMISSIONS;

        foreach ($permissions as $permission) {
            if (array_key_exists($permission, $permissionMappings)) {
                if (! is_null($permissionMappings[$permission])) {
                    array_push($daemonPermissions, $permissionMappings[$permission]);
                }

                $this->permissionRepository->create([
                    'subuser_id' => $subuser->id,
                    'permission' => $permission,
                ]);
            }
        }

        try {
            $this->daemonRepository->setNode($server->node_id)->setAccessServer($server->uuid)
                ->setSubuserKey($subuser->daemonSecret, $daemonPermissions);
            $this->connection->commit();

            return $subuser;
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('admin/exceptions.daemon_connection_failed', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }
    }
}
