<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Servers;

use Ramsey\Uuid\Uuid;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ServerCreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $allocationRepository;

    /**
     * @var \Pterodactyl\Services\Servers\ServerConfigurationStructureService
     */
    protected $configurationStructureService;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    protected $serverVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var \Pterodactyl\Services\Servers\UsernameGenerationService
     */
    protected $usernameService;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    protected $validatorService;

    /**
     * CreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface     $allocationRepository
     * @param \Illuminate\Database\ConnectionInterface                            $connection
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface  $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface           $nodeRepository
     * @param \Pterodactyl\Services\Servers\ServerConfigurationStructureService   $configurationStructureService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface         $repository
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface           $userRepository
     * @param \Pterodactyl\Services\Servers\UsernameGenerationService             $usernameService
     * @param \Pterodactyl\Services\Servers\VariableValidatorService              $validatorService
     */
    public function __construct(
        AllocationRepositoryInterface $allocationRepository,
        ConnectionInterface $connection,
        DaemonServerRepositoryInterface $daemonServerRepository,
        NodeRepositoryInterface $nodeRepository,
        ServerConfigurationStructureService $configurationStructureService,
        ServerRepositoryInterface $repository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        UserRepositoryInterface $userRepository,
        UsernameGenerationService $usernameService,
        VariableValidatorService $validatorService
    ) {
        $this->allocationRepository = $allocationRepository;
        $this->configurationStructureService = $configurationStructureService;
        $this->connection = $connection;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->nodeRepository = $nodeRepository;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->userRepository = $userRepository;
        $this->usernameService = $usernameService;
        $this->validatorService = $validatorService;
    }

    /**
     * Create a server on both the panel and daemon.
     *
     * @param array $data
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function create(array $data)
    {
        // @todo auto-deployment
        $validator = $this->validatorService->isAdmin()->setFields($data['environment'])->validate($data['egg_id']);
        $uniqueShort = str_random(8);

        $this->connection->beginTransaction();

        $server = $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'uuidShort' => $uniqueShort,
            'node_id' => $data['node_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'skip_scripts' => isset($data['skip_scripts']),
            'suspended' => false,
            'owner_id' => $data['owner_id'],
            'memory' => $data['memory'],
            'swap' => $data['swap'],
            'disk' => $data['disk'],
            'io' => $data['io'],
            'cpu' => $data['cpu'],
            'oom_disabled' => isset($data['oom_disabled']),
            'allocation_id' => $data['allocation_id'],
            'nest_id' => $data['nest_id'],
            'egg_id' => $data['egg_id'],
            'pack_id' => (! isset($data['pack_id']) || $data['pack_id'] == 0) ? null : $data['pack_id'],
            'startup' => $data['startup'],
            'daemonSecret' => str_random(NodeCreationService::DAEMON_SECRET_LENGTH),
            'image' => $data['docker_image'],
            'username' => $this->usernameService->generate($data['name'], $uniqueShort),
            'sftp_password' => null,
        ]);

        // Process allocations and assign them to the server in the database.
        $records = [$data['allocation_id']];
        if (isset($data['allocation_additional']) && is_array($data['allocation_additional'])) {
            $records = array_merge($records, $data['allocation_additional']);
        }

        $this->allocationRepository->assignAllocationsToServer($server->id, $records);

        // Process the passed variables and store them in the database.
        $records = [];
        foreach ($validator->getResults() as $result) {
            $records[] = [
                'server_id' => $server->id,
                'variable_id' => $result['id'],
                'variable_value' => $result['value'],
            ];
        }

        $this->serverVariableRepository->insert($records);
        $structure = $this->configurationStructureService->handle($server->id);

        // Create the server on the daemon & commit it to the database.
        try {
            $this->daemonServerRepository->setNode($server->node_id)->create($structure, [
                'start_on_completion' => (bool) array_get($data, 'start_on_completion', false),
            ]);
            $this->connection->commit();
        } catch (RequestException $exception) {
            $this->connection->rollBack();
            throw new DaemonConnectionException($exception);
        }

        return $server;
    }
}
