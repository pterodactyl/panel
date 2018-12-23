<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\User;
use Tests\Traits\MocksUuids;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Tests\Traits\MocksRequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Services\Deployment\FindViableNodesService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Services\Deployment\AllocationSelectionService;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

/**
 * @preserveGlobalState disabled
 */
class ServerCreationServiceTest extends TestCase
{
    use MocksRequestException, MocksUuids;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface|\Mockery\Mock
     */
    private $allocationRepository;

    /**
     * @var \Pterodactyl\Services\Deployment\AllocationSelectionService|\Mockery\Mock
     */
    private $allocationSelectionService;

    /**
     * @var \Pterodactyl\Services\Servers\ServerConfigurationStructureService|\Mockery\Mock
     */
    private $configurationStructureService;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
     */
    private $daemonServerRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    private $eggRepository;

    /**
     * @var \Pterodactyl\Services\Deployment\FindViableNodesService|\Mockery\Mock
     */
    private $findViableNodesService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface|\Mockery\Mock
     */
    private $serverVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    private $userRepository;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService|\Mockery\Mock
     */
    private $validatorService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->allocationRepository = m::mock(AllocationRepositoryInterface::class);
        $this->allocationSelectionService = m::mock(AllocationSelectionService::class);
        $this->configurationStructureService = m::mock(ServerConfigurationStructureService::class);
        $this->connection = m::mock(ConnectionInterface::class);
        $this->daemonServerRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->eggRepository = m::mock(EggRepositoryInterface::class);
        $this->findViableNodesService = m::mock(FindViableNodesService::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->serverVariableRepository = m::mock(ServerVariableRepositoryInterface::class);
        $this->userRepository = m::mock(UserRepositoryInterface::class);
        $this->validatorService = m::mock(VariableValidatorService::class);
    }

    /**
     * Test core functionality of the creation process.
     */
    public function testCreateShouldHitAllOfTheNecessaryServicesAndStoreTheServer()
    {
        $model = factory(Server::class)->make([
            'uuid' => $this->getKnownUuid(),
        ]);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('isUniqueUuidCombo')
            ->once()
            ->with($this->getKnownUuid(), substr($this->getKnownUuid(), 0, 8))
            ->andReturn(true);

        $this->repository->shouldReceive('create')->with(m::subset([
            'uuid' => $this->getKnownUuid(),
            'uuidShort' => substr($this->getKnownUuid(), 0, 8),
            'node_id' => $model->node_id,
            'allocation_id' => $model->allocation_id,
            'owner_id' => $model->owner_id,
            'nest_id' => $model->nest_id,
            'egg_id' => $model->egg_id,
        ]))->once()->andReturn($model);

        $this->allocationRepository->shouldReceive('assignAllocationsToServer')->with($model->id, [$model->allocation_id])->once()->andReturn(1);

        $this->validatorService->shouldReceive('setUserLevel')->with(User::USER_LEVEL_ADMIN)->once()->andReturnSelf();
        $this->validatorService->shouldReceive('handle')->with($model->egg_id, [])->once()->andReturn(
            collect([(object) ['id' => 123, 'value' => 'var1-value']])
        );

        $this->serverVariableRepository->shouldReceive('insert')->with([
            [
                'server_id' => $model->id,
                'variable_id' => 123,
                'variable_value' => 'var1-value',
            ],
        ])->once()->andReturn(true);
        $this->configurationStructureService->shouldReceive('handle')->with($model)->once()->andReturn(['test' => 'struct']);

        $this->daemonServerRepository->shouldReceive('setServer')->with($model)->once()->andReturnSelf();
        $this->daemonServerRepository->shouldReceive('create')->with(['test' => 'struct'], ['start_on_completion' => false])->once();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->getService()->handle($model->toArray());

        $this->assertSame($model, $response);
    }

    /**
     * Test that optional parameters get auto-filled correctly on the model.
     */
    public function testDataIsAutoFilled()
    {
        $model = factory(Server::class)->make(['uuid' => $this->getKnownUuid()]);
        $allocationModel = factory(Allocation::class)->make(['node_id' => $model->node_id]);
        $eggModel = factory(Egg::class)->make(['nest_id' => $model->nest_id]);

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs();
        $this->allocationRepository->shouldReceive('setColumns->find')->once()->with($model->allocation_id)->andReturn($allocationModel);
        $this->eggRepository->shouldReceive('setColumns->find')->once()->with($model->egg_id)->andReturn($eggModel);

        $this->validatorService->shouldReceive('setUserLevel->handle')->once()->andReturn(collect([]));
        $this->repository->shouldReceive('isUniqueUuidCombo')
            ->once()
            ->with($this->getKnownUuid(), substr($this->getKnownUuid(), 0, 8))
            ->andReturn(true);

        $this->repository->shouldReceive('create')->with(m::subset([
            'uuid' => $this->getKnownUuid(),
            'uuidShort' => substr($this->getKnownUuid(), 0, 8),
            'node_id' => $model->node_id,
            'allocation_id' => $model->allocation_id,
            'nest_id' => $model->nest_id,
            'egg_id' => $model->egg_id,
        ]))->andReturn($model);

        $this->allocationRepository->shouldReceive('assignAllocationsToServer')->once()->with($model->id, [$model->allocation_id]);
        $this->configurationStructureService->shouldReceive('handle')->once()->with($model)->andReturn([]);

        $this->daemonServerRepository->shouldReceive('setServer->create')->once();
        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $this->getService()->handle(
            collect($model->toArray())->except(['node_id', 'nest_id'])->toArray()
        );
    }

    /**
     * Test that an auto-deployment object is used correctly if passed.
     */
    public function testAutoDeploymentObject()
    {
        $model = factory(Server::class)->make(['uuid' => $this->getKnownUuid()]);
        $deploymentObject = new DeploymentObject();
        $deploymentObject->setPorts(['25565']);
        $deploymentObject->setDedicated(false);
        $deploymentObject->setLocations([1]);

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs();

        $this->findViableNodesService->shouldReceive('setLocations')->once()->with($deploymentObject->getLocations())->andReturnSelf();
        $this->findViableNodesService->shouldReceive('setDisk')->once()->with($model->disk)->andReturnSelf();
        $this->findViableNodesService->shouldReceive('setMemory')->once()->with($model->memory)->andReturnSelf();
        $this->findViableNodesService->shouldReceive('handle')->once()->withNoArgs()->andReturn([1, 2]);

        $allocationModel = factory(Allocation::class)->make([
            'id' => $model->allocation_id,
            'node_id' => $model->node_id,
        ]);
        $this->allocationSelectionService->shouldReceive('setDedicated')->once()->with($deploymentObject->isDedicated())->andReturnSelf();
        $this->allocationSelectionService->shouldReceive('setNodes')->once()->with([1, 2])->andReturnSelf();
        $this->allocationSelectionService->shouldReceive('setPorts')->once()->with($deploymentObject->getPorts())->andReturnSelf();
        $this->allocationSelectionService->shouldReceive('handle')->once()->withNoArgs()->andReturn($allocationModel);

        $this->validatorService->shouldReceive('setUserLevel->handle')->once()->andReturn(collect([]));
        $this->repository->shouldReceive('isUniqueUuidCombo')
            ->once()
            ->with($this->getKnownUuid(), substr($this->getKnownUuid(), 0, 8))
            ->andReturn(true);

        $this->repository->shouldReceive('create')->with(m::subset([
            'uuid' => $this->getKnownUuid(),
            'uuidShort' => substr($this->getKnownUuid(), 0, 8),
            'node_id' => $model->node_id,
            'allocation_id' => $model->allocation_id,
            'nest_id' => $model->nest_id,
            'egg_id' => $model->egg_id,
        ]))->andReturn($model);

        $this->allocationRepository->shouldReceive('assignAllocationsToServer')->once()->with($model->id, [$model->allocation_id]);
        $this->configurationStructureService->shouldReceive('handle')->once()->with($model)->andReturn([]);

        $this->daemonServerRepository->shouldReceive('setServer->create')->once();
        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $this->getService()->handle(
            collect($model->toArray())->except(['allocation_id', 'node_id'])->toArray(), $deploymentObject
        );
    }

    /**
     * Test handling of node timeout or other daemon error.
     *
     * @expectedException \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function testExceptionShouldBeThrownIfTheRequestFails()
    {
        $this->configureExceptionMock();

        $model = factory(Server::class)->make([
            'uuid' => $this->getKnownUuid(),
        ]);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('isUniqueUuidCombo')->once()->andReturn(true);
        $this->repository->shouldReceive('create')->once()->andReturn($model);
        $this->allocationRepository->shouldReceive('assignAllocationsToServer')->once()->andReturn(1);
        $this->validatorService->shouldReceive('setUserLevel')->once()->andReturnSelf();
        $this->validatorService->shouldReceive('handle')->once()->andReturn(collect([]));
        $this->configurationStructureService->shouldReceive('handle')->once()->andReturn([]);

        $this->daemonServerRepository->shouldReceive('setServer')->with($model)->once()->andThrow($this->getExceptionMock());
        $this->connection->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();

        $this->getService()->handle($model->toArray());
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Servers\ServerCreationService
     */
    private function getService(): ServerCreationService
    {
        return new ServerCreationService(
            $this->allocationRepository,
            $this->allocationSelectionService,
            $this->connection,
            $this->daemonServerRepository,
            $this->eggRepository,
            $this->findViableNodesService,
            $this->configurationStructureService,
            $this->repository,
            $this->serverVariableRepository,
            $this->validatorService
        );
    }
}
