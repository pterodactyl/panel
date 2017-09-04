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

use Illuminate\Log\Writer;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class SubuserCreationService
{
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
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * SubuserCreationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \Pterodactyl\Services\Users\UserCreationService                    $userCreationService
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     * @param \Pterodactyl\Services\Subusers\PermissionCreationService           $permissionService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface        $serverRepository
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface       $subuserRepository
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface          $userRepository
     * @param \Illuminate\Log\Writer                                             $writer
     */
    public function __construct(
        ConnectionInterface $connection,
        UserCreationService $userCreationService,
        DaemonServerRepositoryInterface $daemonRepository,
        PermissionCreationService $permissionService,
        ServerRepositoryInterface $serverRepository,
        SubuserRepositoryInterface $subuserRepository,
        UserRepositoryInterface $userRepository,
        Writer $writer
    ) {
        $this->connection = $connection;
        $this->daemonRepository = $daemonRepository;
        $this->permissionService = $permissionService;
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
            $user = $this->userCreationService->handle([
                'email' => $email,
                'username' => substr(strtok($email, '@'), 0, 8) . '_' . str_random(6),
                'name_first' => 'Server',
                'name_last' => 'Subuser',
                'root_admin' => false,
            ]);
        }

        $subuser = $this->subuserRepository->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'daemonSecret' => bin2hex(random_bytes(self::DAEMON_SECRET_BYTES)),
        ]);

        $daemonPermissions = $this->permissionService->handle($subuser->id, $permissions);

        try {
            $this->daemonRepository->setNode($server->node_id)->setAccessServer($server->uuid)
                ->setSubuserKey($subuser->daemonSecret, $daemonPermissions);
            $this->connection->commit();

            return $subuser;
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
