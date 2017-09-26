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
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class StartupModificationService
{
    /**
     * @var bool
     */
    protected $admin = false;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $database;

    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService
     */
    protected $environmentService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    protected $serverVariableRepository;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    protected $validatorService;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * StartupModificationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                            $database
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface  $daemonServerRepository
     * @param \Pterodactyl\Services\Servers\EnvironmentService                    $environmentService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface         $repository
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Pterodactyl\Services\Servers\VariableValidatorService              $validatorService
     * @param \Illuminate\Log\Writer                                              $writer
     */
    public function __construct(
        ConnectionInterface $database,
        DaemonServerRepositoryInterface $daemonServerRepository,
        EnvironmentService $environmentService,
        ServerRepositoryInterface $repository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        VariableValidatorService $validatorService,
        Writer $writer
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->database = $database;
        $this->environmentService = $environmentService;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->validatorService = $validatorService;
        $this->writer = $writer;
    }

    /**
     * Determine if this function should run at an administrative level.
     *
     * @param bool $bool
     * @return $this
     */
    public function isAdmin($bool = true)
    {
        $this->admin = $bool;

        return $this;
    }

    /**
     * Process startup modification for a server.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param array                          $data
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($server, array $data)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        if (
            $server->service_id != array_get($data, 'service_id', $server->service_id) ||
            $server->option_id != array_get($data, 'option_id', $server->option_id) ||
            $server->pack_id != array_get($data, 'pack_id', $server->pack_id)
        ) {
            $hasServiceChanges = true;
        }

        $this->database->beginTransaction();
        if (isset($data['environment'])) {
            $validator = $this->validatorService->isAdmin($this->admin)
                ->setFields($data['environment'])
                ->validate(array_get($data, 'option_id', $server->option_id));

            foreach ($validator->getResults() as $result) {
                $this->serverVariableRepository->withoutFresh()->updateOrCreate([
                    'server_id' => $server->id,
                    'variable_id' => $result['id'],
                ], [
                    'variable_value' => $result['value'],
                ]);
            }
        }

        $daemonData = [
            'build' => [
                'env|overwrite' => $this->environmentService->process($server),
            ],
        ];

        if ($this->admin) {
            $server = $this->repository->update($server->id, [
                'installed' => 0,
                'startup' => array_get($data, 'startup', $server->startup),
                'service_id' => array_get($data, 'service_id', $server->service_id),
                'option_id' => array_get($data, 'option_id', $server->service_id),
                'pack_id' => array_get($data, 'pack_id', $server->pack_id),
                'skip_scripts' => isset($data['skip_scripts']),
            ]);

            if (isset($hasServiceChanges)) {
                $daemonData['service'] = array_merge(
                    $this->repository->withColumns(['id', 'option_id', 'pack_id'])->getDaemonServiceData($server),
                    ['skip_scripts' => isset($data['skip_scripts'])]
                );
            }
        }

        try {
            $this->daemonServerRepository->setNode($server->node_id)->setAccessServer($server->uuid)->update($daemonData);
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
