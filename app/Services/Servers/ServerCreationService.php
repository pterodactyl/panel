<?php

namespace Pterodactyl\Services\Servers;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Allocation;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Services\Deployment\FindViableNodesService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Services\Deployment\AllocationSelectionService;
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
     * @var \Pterodactyl\Services\Deployment\AllocationSelectionService
     */
    private $allocationSelectionService;

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
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    private $eggRepository;

    /**
     * @var \Pterodactyl\Services\Deployment\FindViableNodesService
     */
    private $findViableNodesService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    private $serverVariableRepository;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    private $validatorService;

    /**
     * CreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface     $allocationRepository
     * @param \Pterodactyl\Services\Deployment\AllocationSelectionService         $allocationSelectionService
     * @param \Illuminate\Database\ConnectionInterface                            $connection
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface  $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface            $eggRepository
     * @param \Pterodactyl\Services\Deployment\FindViableNodesService             $findViableNodesService
     * @param \Pterodactyl\Services\Servers\ServerConfigurationStructureService   $configurationStructureService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface         $repository
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Pterodactyl\Services\Servers\VariableValidatorService              $validatorService
     */
    public function __construct(
        AllocationRepositoryInterface $allocationRepository,
        AllocationSelectionService $allocationSelectionService,
        ConnectionInterface $connection,
        DaemonServerRepositoryInterface $daemonServerRepository,
        EggRepositoryInterface $eggRepository,
        FindViableNodesService $findViableNodesService,
        ServerConfigurationStructureService $configurationStructureService,
        ServerRepositoryInterface $repository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        VariableValidatorService $validatorService
    ) {
        $this->allocationSelectionService = $allocationSelectionService;
        $this->allocationRepository = $allocationRepository;
        $this->configurationStructureService = $configurationStructureService;
        $this->connection = $connection;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->eggRepository = $eggRepository;
        $this->findViableNodesService = $findViableNodesService;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->validatorService = $validatorService;
    }

    /**
     * Create a server on the Panel and trigger a request to the Daemon to begin the server
     * creation process. This function will attempt to set as many additional values
     * as possible given the input data. For example, if an allocation_id is passed with
     * no node_id the node_is will be picked from the allocation.
     *
     * @param array                                             $data
     * @param \Pterodactyl\Models\Objects\DeploymentObject|null $deployment
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException
     */
    public function handle(array $data, DeploymentObject $deployment = null): Server
    {
        $this->connection->beginTransaction();

        // If a deployment object has been passed we need to get the allocation
        // that the server should use, and assign the node from that allocation.
        if ($deployment instanceof DeploymentObject) {
            $allocation = $this->configureDeployment($data, $deployment);
            $data['allocation_id'] = $allocation->id;
            $data['node_id'] = $allocation->node_id;
        }

        // Auto-configure the node based on the selected allocation
        // if no node was defined.
        if (is_null(array_get($data, 'node_id'))) {
            $data['node_id'] = $this->getNodeFromAllocation($data['allocation_id']);
        }

        if (is_null(array_get($data, 'nest_id'))) {
            $egg = $this->eggRepository->setColumns(['id', 'nest_id'])->find(array_get($data, 'egg_id'));
            $data['nest_id'] = $egg->nest_id;
        }

        $eggVariableData = $this->validatorService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle(array_get($data, 'egg_id'), array_get($data, 'environment', []));

        // Create the server and assign any additional allocations to it.
        $server = $this->createModel($data);
        $this->storeAssignedAllocations($server, $data);
        $this->storeEggVariables($server, $eggVariableData);

        $structure = $this->configurationStructureService->handle($server);

        try {
            $this->daemonServerRepository->setServer($server)->create($structure, [
                'start_on_completion' => (bool) array_get($data, 'start_on_completion', false),
            ]);

            $this->connection->commit();
        } catch (RequestException $exception) {
            $this->connection->rollBack();
            throw new DaemonConnectionException($exception);
        }

        return $server;
    }

    /**
     * Gets an allocation to use for automatic deployment.
     *
     * @param array                                        $data
     * @param \Pterodactyl\Models\Objects\DeploymentObject $deployment
     *
     * @return \Pterodactyl\Models\Allocation
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     */
    private function configureDeployment(array $data, DeploymentObject $deployment): Allocation
    {
        $nodes = $this->findViableNodesService->setLocations($deployment->getLocations())
            ->setDisk(array_get($data, 'disk'))
            ->setMemory(array_get($data, 'memory'))
            ->handle();

        return $this->allocationSelectionService->setDedicated($deployment->isDedicated())
            ->setNodes($nodes)
            ->setPorts($deployment->getPorts())
            ->handle();
    }

    /**
     * Store the server in the database and return the model.
     *
     * @param array $data
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    private function createModel(array $data): Server
    {
        $uuid = $this->generateUniqueUuidCombo();

        return $this->repository->create([
            'external_id' => array_get($data, 'external_id'),
            'uuid' => $uuid,
            'uuidShort' => substr($uuid, 0, 8),
            'node_id' => array_get($data, 'node_id'),
            'name' => array_get($data, 'name'),
            'description' => array_get($data, 'description') ?? '',
            'skip_scripts' => array_get($data, 'skip_scripts') ?? isset($data['skip_scripts']),
            'suspended' => false,
            'owner_id' => array_get($data, 'owner_id'),
            'memory' => array_get($data, 'memory'),
            'swap' => array_get($data, 'swap'),
            'disk' => array_get($data, 'disk'),
            'io' => array_get($data, 'io'),
            'cpu' => array_get($data, 'cpu'),
            'oom_disabled' => array_get($data, 'oom_disabled', true),
            'allocation_id' => array_get($data, 'allocation_id'),
            'nest_id' => array_get($data, 'nest_id'),
            'egg_id' => array_get($data, 'egg_id'),
            'pack_id' => (! isset($data['pack_id']) || $data['pack_id'] == 0) ? null : $data['pack_id'],
            'startup' => array_get($data, 'startup'),
            'daemonSecret' => str_random(Node::DAEMON_SECRET_LENGTH),
            'image' => array_get($data, 'image'),
            'database_limit' => array_get($data, 'database_limit'),
            'allocation_limit' => array_get($data, 'allocation_limit'),
        ]);
    }

    /**
     * Configure the allocations assigned to this server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param array                      $data
     */
    private function storeAssignedAllocations(Server $server, array $data)
    {
        $records = [$data['allocation_id']];
        if (isset($data['allocation_additional']) && is_array($data['allocation_additional'])) {
            $records = array_merge($records, $data['allocation_additional']);
        }

        $this->allocationRepository->assignAllocationsToServer($server->id, $records);
    }

    /**
     * Process environment variables passed for this server and store them in the database.
     *
     * @param \Pterodactyl\Models\Server     $server
     * @param \Illuminate\Support\Collection $variables
     */
    private function storeEggVariables(Server $server, Collection $variables)
    {
        $records = $variables->map(function ($result) use ($server) {
            return [
                'server_id' => $server->id,
                'variable_id' => $result->id,
                'variable_value' => $result->value,
            ];
        })->toArray();

        if (! empty($records)) {
            $this->serverVariableRepository->insert($records);
        }
    }

    /**
     * Get the node that an allocation belongs to.
     *
     * @param int $allocation
     * @return int
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    private function getNodeFromAllocation(int $allocation): int
    {
        $allocation = $this->allocationRepository->setColumns(['id', 'node_id'])->find($allocation);

        return $allocation->node_id;
    }

    /**
     * Create a unique UUID and UUID-Short combo for a server.
     *
     * @return string
     */
    private function generateUniqueUuidCombo(): string
    {
        $uuid = Uuid::uuid4()->toString();

        if (! $this->repository->isUniqueUuidCombo($uuid, substr($uuid, 0, 8))) {
            return $this->generateUniqueUuidCombo();
        }

        return $uuid;
    }
}
