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
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Contracts\Repository\PermissionRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class SubuserUpdateService
{
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
     * @var \Pterodactyl\Services\Subusers\PermissionCreationService
     */
    protected $permissionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * SubuserUpdateService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     * @param \Pterodactyl\Services\Subusers\PermissionCreationService           $permissionService
     * @param \Pterodactyl\Contracts\Repository\PermissionRepositoryInterface    $permissionRepository
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface       $repository
     * @param \Illuminate\Log\Writer                                             $writer
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepositoryInterface $daemonRepository,
        PermissionCreationService $permissionService,
        PermissionRepositoryInterface $permissionRepository,
        SubuserRepositoryInterface $repository,
        Writer $writer
    ) {
        $this->connection = $connection;
        $this->daemonRepository = $daemonRepository;
        $this->permissionRepository = $permissionRepository;
        $this->permissionService = $permissionService;
        $this->repository = $repository;
        $this->writer = $writer;
    }

    /**
     * Update permissions for a given subuser.
     *
     * @param int   $subuser
     * @param array $permissions
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($subuser, array $permissions)
    {
        $subuser = $this->repository->getWithServer($subuser);

        $this->connection->beginTransaction();
        $this->permissionRepository->deleteWhere([['subuser_id', '=', $subuser->id]]);
        $daemonPermissions = $this->permissionService->handle($subuser->id, $permissions);

        try {
            $this->daemonRepository->setNode($subuser->server->node_id)->setAccessServer($subuser->server->uuid)
                ->setSubuserKey($subuser->daemonSecret, $daemonPermissions);
            $this->connection->commit();
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
