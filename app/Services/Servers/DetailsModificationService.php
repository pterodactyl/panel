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

use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\DatabaseManager;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Daemon\ServerRepository as DaemonServerRepository;

class DetailsModificationService
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * @var \Pterodactyl\Repositories\Daemon\ServerRepository
     */
    protected $daemonServerRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    protected $repository;

    /**
     * DetailsModificationService constructor.
     *
     * @param \Illuminate\Database\DatabaseManager                $database
     * @param \Pterodactyl\Repositories\Daemon\ServerRepository   $daemonServerRepository
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     */
    public function __construct(
        DatabaseManager $database,
        DaemonServerRepository $daemonServerRepository,
        ServerRepository $repository
    ) {
        $this->database = $database;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->repository = $repository;
    }

    /**
     * Update the details for a single server instance.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param array                          $data
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function edit($server, array $data)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        $this->database->beginTransaction();
        $currentSecret = $server->daemonSecret;

        if (
            (isset($data['reset_token']) && ! is_null($data['reset_token'])) ||
            (isset($data['owner_id']) && $data['owner_id'] != $server->owner_id)
        ) {
            $data['daemonSecret'] = bin2hex(random_bytes(18));
            $shouldUpdate = true;
        }

        $this->repository->withoutFresh()->update($server->id, [
            'owner_id' => array_get($data, 'owner_id') ?? $server->owner_id,
            'name' => array_get($data, 'name') ?? $server->name,
            'description' => array_get($data, 'description') ?? $server->description,
            'daemonSecret' => array_get($data, 'daemonSecret') ?? $server->daemonSecret,
        ], true, true);

        // If there are no updates, lets save the changes and return.
        if (! isset($shouldUpdate)) {
            return $this->database->commit();
        }

        try {
            $this->daemonServerRepository->setNode($server->node_id)->setAccessServer($server->uuid)->update([
                'keys' => [
                    (string) $currentSecret => [],
                    (string) $data['daemonSecret'] => $this->daemonServerRepository::DAEMON_PERMISSIONS,
                ],
            ]);

            return $this->database->commit();
        } catch (RequestException $exception) {
            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => $exception->getResponse()->getStatusCode(),
            ]));
        }
    }

    /**
     * Update the docker container for a specified server.
     *
     * @param  int|\Pterodactyl\Models\Server $server
     * @param  string                         $image
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function setDockerImage($server, $image)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        $this->database->beginTransaction();
        $this->repository->withoutFresh()->update($server->id, ['image' => $image]);

        try {
            $this->daemonServerRepository->setNode($server->node_id)->setAccessServer($server->uuid)->update([
                'build' => [
                    'image' => $image,
                ],
            ]);

            $this->database->commit();
        } catch (RequestException $exception) {
            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => $exception->getResponse()->getStatusCode(),
            ]));
        }
    }
}
