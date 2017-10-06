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
use Illuminate\Log\Writer;
use Illuminate\Database\DatabaseManager;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
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
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

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
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * CreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface     $allocationRepository
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface  $daemonServerRepository
     * @param \Illuminate\Database\DatabaseManager                                $database
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface           $nodeRepository
     * @param \Pterodactyl\Services\Servers\ServerConfigurationStructureService   $configurationStructureService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface         $repository
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface           $userRepository
     * @param \Pterodactyl\Services\Servers\UsernameGenerationService             $usernameService
     * @param \Pterodactyl\Services\Servers\VariableValidatorService              $validatorService
     * @param \Illuminate\Log\Writer                                              $writer
     */
    public function __construct(
        AllocationRepositoryInterface $allocationRepository,
        DaemonServerRepositoryInterface $daemonServerRepository,
        DatabaseManager $database,
        NodeRepositoryInterface $nodeRepository,
        ServerConfigurationStructureService $configurationStructureService,
        ServerRepositoryInterface $repository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        UserRepositoryInterface $userRepository,
        UsernameGenerationService $usernameService,
        VariableValidatorService $validatorService,
        Writer $writer
    ) {
        $this->allocationRepository = $allocationRepository;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->configurationStructureService = $configurationStructureService;
        $this->database = $database;
        $this->nodeRepository = $nodeRepository;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->userRepository = $userRepository;
        $this->usernameService = $usernameService;
        $this->validatorService = $validatorService;
        $this->writer = $writer;
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
        $validator = $this->validatorService->isAdmin()->setFields($data['environment'])->validate($data['option_id']);
        $uniqueShort = str_random(8);

        $this->database->beginTransaction();

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
            'service_id' => $data['service_id'],
            'option_id' => $data['option_id'],
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
            $this->daemonServerRepository->setNode($server->node_id)->create($structure, ['start_on_completion' => (bool) $data['start_on_completion']]);
            $this->database->commit();
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);
            $this->database->rollBack();

            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }

        return $server;
    }
}
