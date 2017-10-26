<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
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
    protected $connection;

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
     * StartupModificationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                            $connection
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface  $daemonServerRepository
     * @param \Pterodactyl\Services\Servers\EnvironmentService                    $environmentService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface         $repository
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Pterodactyl\Services\Servers\VariableValidatorService              $validatorService
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepositoryInterface $daemonServerRepository,
        EnvironmentService $environmentService,
        ServerRepositoryInterface $repository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        VariableValidatorService $validatorService
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->connection = $connection;
        $this->environmentService = $environmentService;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->validatorService = $validatorService;
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
            $server->nest_id != array_get($data, 'nest_id', $server->nest_id) ||
            $server->egg_id != array_get($data, 'egg_id', $server->egg_id) ||
            $server->pack_id != array_get($data, 'pack_id', $server->pack_id)
        ) {
            $hasServiceChanges = true;
        }

        $this->connection->beginTransaction();
        if (! is_null(array_get($data, 'environment'))) {
            $validator = $this->validatorService->isAdmin($this->admin)
                ->setFields(array_get($data, 'environment', []))
                ->validate(array_get($data, 'egg_id', $server->egg_id));

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
                'nest_id' => array_get($data, 'nest_id', $server->nest_id),
                'egg_id' => array_get($data, 'egg_id', $server->egg_id),
                'pack_id' => array_get($data, 'pack_id', $server->pack_id) > 0 ? array_get($data, 'pack_id', $server->pack_id) : null,
                'skip_scripts' => isset($data['skip_scripts']),
            ]);

            if (isset($hasServiceChanges)) {
                $daemonData['service'] = array_merge(
                    $this->repository->withColumns(['id', 'egg_id', 'pack_id'])->getDaemonServiceData($server->id),
                    ['skip_scripts' => isset($data['skip_scripts'])]
                );
            }
        }

        try {
            $this->daemonServerRepository->setNode($server->node_id)->setAccessServer($server->uuid)->update($daemonData);
        } catch (RequestException $exception) {
            $this->connection->rollBack();
            throw new DaemonConnectionException($exception);
        }

        $this->connection->commit();
    }
}
