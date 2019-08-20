<?php

namespace App\Services\Servers;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Node;
use App\Models\User;
use App\Models\Server;
use Illuminate\Support\Collection;
use App\Models\Allocation;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use App\Models\Objects\DeploymentObject;
use App\Services\Deployment\FindViableNodesService;
use App\Contracts\Repository\EggRepositoryInterface;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Services\Deployment\AllocationSelectionService;
use App\Contracts\Repository\AllocationRepositoryInterface;
use App\Exceptions\Http\Connection\DaemonConnectionException;
use App\Contracts\Repository\ServerVariableRepositoryInterface;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ServerCreationService
{
    /**
     * @var \App\Contracts\Repository\AllocationRepositoryInterface
     */
    private $allocationRepository;

    /**
     * @var \App\Services\Deployment\AllocationSelectionService
     */
    private $allocationSelectionService;

    /**
     * @var \App\Services\Servers\ServerConfigurationStructureService
     */
    private $configurationStructureService;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \App\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonServerRepository;

    /**
     * @var \App\Contracts\Repository\EggRepositoryInterface
     */
    private $eggRepository;

    /**
     * @var \App\Services\Deployment\FindViableNodesService
     */
    private $findViableNodesService;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \App\Contracts\Repository\ServerVariableRepositoryInterface
     */
    private $serverVariableRepository;

    /**
     * @var \App\Services\Servers\VariableValidatorService
     */
    private $validatorService;

    /**
     * CreationService constructor.
     *
     * @param \App\Contracts\Repository\AllocationRepositoryInterface     $allocationRepository
     * @param \App\Services\Deployment\AllocationSelectionService         $allocationSelectionService
     * @param \Illuminate\Database\ConnectionInterface                            $connection
     * @param \App\Contracts\Repository\Daemon\ServerRepositoryInterface  $daemonServerRepository
     * @param \App\Contracts\Repository\EggRepositoryInterface            $eggRepository
     * @param \App\Services\Deployment\FindViableNodesService             $findViableNodesService
     * @param \App\Services\Servers\ServerConfigurationStructureService   $configurationStructureService
     * @param \App\Contracts\Repository\ServerRepositoryInterface         $repository
     * @param \App\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \App\Services\Servers\VariableValidatorService              $validatorService
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
     * @param \App\Models\Objects\DeploymentObject|null $deployment
     * @return \App\Models\Server
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Deployment\NoViableNodeException
     * @throws \App\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \App\Exceptions\Service\Deployment\NoViableAllocationException
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
        if (is_null(Arr::get($data, 'node_id'))) {
            $data['node_id'] = $this->getNodeFromAllocation($data['allocation_id']);
        }

        if (is_null(Arr::get($data, 'nest_id'))) {
            $egg = $this->eggRepository->setColumns(['id', 'nest_id'])->find(Arr::get($data, 'egg_id'));
            $data['nest_id'] = $egg->nest_id;
        }

        $eggVariableData = $this->validatorService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle(Arr::get($data, 'egg_id'), Arr::get($data, 'environment', []));

        // Create the server and assign any additional allocations to it.
        $server = $this->createModel($data);
        $this->storeAssignedAllocations($server, $data);
        $this->storeEggVariables($server, $eggVariableData);

        $structure = $this->configurationStructureService->handle($server);

        try {
            $this->daemonServerRepository->setServer($server)->create($structure, [
                'start_on_completion' => (bool) Arr::get($data, 'start_on_completion', false),
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
     * @param \App\Models\Objects\DeploymentObject $deployment
     *
     * @return \App\Models\Allocation
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \App\Exceptions\Service\Deployment\NoViableNodeException
     */
    private function configureDeployment(array $data, DeploymentObject $deployment): Allocation
    {
        $nodes = $this->findViableNodesService->setLocations($deployment->getLocations())
            ->setDisk(Arr::get($data, 'disk'))
            ->setMemory(Arr::get($data, 'memory'))
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
     * @return \App\Models\Server
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    private function createModel(array $data): Server
    {
        $uuid = $this->generateUniqueUuidCombo();

        return $this->repository->create([
            'external_id' => Arr::get($data, 'external_id'),
            'uuid' => $uuid,
            'uuidShort' => substr($uuid, 0, 8),
            'node_id' => Arr::get($data, 'node_id'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description') ?? '',
            'skip_scripts' => Arr::get($data, 'skip_scripts') ?? isset($data['skip_scripts']),
            'suspended' => false,
            'owner_id' => Arr::get($data, 'owner_id'),
            'memory' => Arr::get($data, 'memory'),
            'swap' => Arr::get($data, 'swap'),
            'disk' => Arr::get($data, 'disk'),
            'io' => Arr::get($data, 'io'),
            'cpu' => Arr::get($data, 'cpu'),
            'oom_disabled' => Arr::get($data, 'oom_disabled', true),
            'allocation_id' => Arr::get($data, 'allocation_id'),
            'nest_id' => Arr::get($data, 'nest_id'),
            'egg_id' => Arr::get($data, 'egg_id'),
            'pack_id' => (! isset($data['pack_id']) || $data['pack_id'] == 0) ? null : $data['pack_id'],
            'startup' => Arr::get($data, 'startup'),
            'daemonSecret' => Str::random(Node::DAEMON_SECRET_LENGTH),
            'image' => Arr::get($data, 'image'),
            'database_limit' => Arr::get($data, 'database_limit'),
            'allocation_limit' => Arr::get($data, 'allocation_limit'),
        ]);
    }

    /**
     * Configure the allocations assigned to this server.
     *
     * @param \App\Models\Server $server
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
     * @param \App\Models\Server     $server
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
     * @throws \App\Exceptions\Repository\RecordNotFoundException
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
