<?php

namespace Pterodactyl\Services\Servers;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
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
    private $allocationRepository;

    /**
     * @var \Pterodactyl\Services\Servers\ServerConfigurationStructureService
     */
    private $configurationStructureService;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonServerRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    private $nodeRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    private $serverVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    private $validatorService;

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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(array $data)
    {
        // @todo auto-deployment

        $this->connection->beginTransaction();
        $server = $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'uuidShort' => str_random(8),
            'node_id' => array_get($data, 'node_id'),
            'name' => array_get($data, 'name'),
            'description' => array_get($data, 'description') ?? '',
            'skip_scripts' => isset($data['skip_scripts']),
            'suspended' => false,
            'owner_id' => array_get($data, 'owner_id'),
            'memory' => array_get($data, 'memory'),
            'swap' => array_get($data, 'swap'),
            'disk' => array_get($data, 'disk'),
            'io' => array_get($data, 'io'),
            'cpu' => array_get($data, 'cpu'),
            'oom_disabled' => false,
            'allocation_id' => array_get($data, 'allocation_id'),
            'nest_id' => array_get($data, 'nest_id'),
            'egg_id' => array_get($data, 'egg_id'),
            'pack_id' => (! isset($data['pack_id']) || $data['pack_id'] == 0) ? null : $data['pack_id'],
            'startup' => array_get($data, 'startup'),
            'daemonSecret' => str_random(Node::DAEMON_SECRET_LENGTH),
            'image' => array_get($data, 'docker_image'),
        ]);

        // Process allocations and assign them to the server in the database.
        $records = [$data['allocation_id']];
        if (isset($data['allocation_additional']) && is_array($data['allocation_additional'])) {
            $records = array_merge($records, $data['allocation_additional']);
        }

        $this->allocationRepository->assignAllocationsToServer($server->id, $records);

        // Process the passed variables and store them in the database.
        $this->validatorService->setUserLevel(User::USER_LEVEL_ADMIN);
        $results = $this->validatorService->handle(array_get($data, 'egg_id'), array_get($data, 'environment', []));

        $records = $results->map(function ($result) use ($server) {
            return [
                'server_id' => $server->id,
                'variable_id' => $result->id,
                'variable_value' => $result->value,
            ];
        })->toArray();

        if (! empty($records)) {
            $this->serverVariableRepository->insert($records);
        }
        $structure = $this->configurationStructureService->handle($server);

        // Create the server on the daemon & commit it to the database.
        $node = $this->nodeRepository->find($server->node_id);
        try {
            $this->daemonServerRepository->setNode($node)->create($structure, [
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
