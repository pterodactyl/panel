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

namespace Pterodactyl\Services\Servers;

use Illuminate\Log\Writer;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService;
use Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService;
use Pterodactyl\Repositories\Daemon\ServerRepository as DaemonServerRepository;

class DetailsModificationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Repositories\Daemon\ServerRepository
     */
    protected $daemonServerRepository;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService
     */
    protected $keyCreationService;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService
     */
    protected $keyDeletionService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    protected $repository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * DetailsModificationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                  $connection
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService $keyCreationService
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService $keyDeletionService
     * @param \Pterodactyl\Repositories\Daemon\ServerRepository         $daemonServerRepository
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository       $repository
     * @param \Illuminate\Log\Writer                                    $writer
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonKeyCreationService $keyCreationService,
        DaemonKeyDeletionService $keyDeletionService,
        DaemonServerRepository $daemonServerRepository,
        ServerRepository $repository,
        Writer $writer
    ) {
        $this->connection = $connection;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->keyCreationService = $keyCreationService;
        $this->keyDeletionService = $keyDeletionService;
        $this->repository = $repository;
        $this->writer = $writer;
    }

    /**
     * Update the details for a single server instance.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param array                          $data
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function edit($server, array $data)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        $this->connection->beginTransaction();
        $this->repository->withoutFresh()->update($server->id, [
            'owner_id' => array_get($data, 'owner_id') ?? $server->owner_id,
            'name' => array_get($data, 'name') ?? $server->name,
            'description' => array_get($data, 'description') ?? $server->description,
        ], true, true);

        if (array_get($data, 'owner_id') != $server->owner_id) {
            $this->keyDeletionService->handle($server, $server->owner_id);
            $this->keyCreationService->handle($server->id, array_get($data, 'owner_id'));
        }

        $this->connection->commit();
    }

    /**
     * Update the docker container for a specified server.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param string                         $image
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function setDockerImage($server, $image)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        $this->connection->beginTransaction();
        $this->repository->withoutFresh()->update($server->id, ['image' => $image]);

        try {
            $this->daemonServerRepository->setNode($server->node_id)->setAccessServer($server->uuid)->update([
                'build' => [
                    'image' => $image,
                ],
            ]);
        } catch (RequestException $exception) {
            $this->connection->rollBack();
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }

        $this->connection->commit();
    }
}
