<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Servers;

use Illuminate\Log\Writer;
use Pterodactyl\Models\Server;
use Illuminate\Database\DatabaseManager;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Nodes\NodeCreationService;
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
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * DetailsModificationService constructor.
     *
     * @param \Illuminate\Database\DatabaseManager                $database
     * @param \Pterodactyl\Repositories\Daemon\ServerRepository   $daemonServerRepository
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     * @param \Illuminate\Log\Writer                              $writer
     */
    public function __construct(
        DatabaseManager $database,
        DaemonServerRepository $daemonServerRepository,
        ServerRepository $repository,
        Writer $writer
    ) {
        $this->database = $database;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->repository = $repository;
        $this->writer = $writer;
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
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
            $data['daemonSecret'] = str_random(NodeCreationService::DAEMON_SECRET_LENGTH);
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
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }
    }

    /**
     * Update the docker container for a specified server.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param string                         $image
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
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }
    }
}
