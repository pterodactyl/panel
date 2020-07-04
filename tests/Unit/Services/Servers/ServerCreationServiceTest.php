<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use GuzzleHttp\Psr7\Request;
use Pterodactyl\Models\User;
use Tests\Traits\MocksUuids;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Tests\Traits\MocksRequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Repositories\Eloquent\EggRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;
use Pterodactyl\Services\Deployment\FindViableNodesService;
use Pterodactyl\Repositories\Eloquent\ServerVariableRepository;
use Pterodactyl\Services\Deployment\AllocationSelectionService;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;

/**
 * @preserveGlobalState disabled
 */
class ServerCreationServiceTest extends TestCase
{
    use MocksRequestException, MocksUuids;

    /**
     * @var \Mockery\MockInterface
     */
    private $allocationRepository;

    /**
     * @var \Mockery\MockInterface
     */
    private $allocationSelectionService;

    /**
     * @var \Mockery\MockInterface
     */
    private $configurationStructureService;

    /**
     * @var \Mockery\MockInterface
     */
    private $connection;

    /**
     * @var \Mockery\MockInterface
     */
    private $daemonServerRepository;

    /**
     * @var \Mockery\MockInterface
     */
    private $eggRepository;

    /**
     * @var \Mockery\MockInterface
     */
    private $findViableNodesService;

    /**
     * @var \Mockery\MockInterface
     */
    private $repository;

    /**
     * @var \Mockery\MockInterface
     */
    private $serverVariableRepository;

    /**
     * @var \Mockery\MockInterface
     */
    private $validatorService;

    /**
     * @var \Mockery\MockInterface
     */
    private $serverDeletionService;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->allocationRepository = m::mock(AllocationRepository::class);
        $this->allocationSelectionService = m::mock(AllocationSelectionService::class);
        $this->configurationStructureService = m::mock(ServerConfigurationStructureService::class);
        $this->connection = m::mock(ConnectionInterface::class);
        $this->findViableNodesService = m::mock(FindViableNodesService::class);
        $this->validatorService = m::mock(VariableValidatorService::class);
        $this->eggRepository = m::mock(EggRepository::class);
        $this->repository = m::mock(ServerRepository::class);
        $this->serverVariableRepository = m::mock(ServerVariableRepository::class);
        $this->daemonServerRepository = m::mock(DaemonServerRepository::class);
        $this->serverDeletionService = m::mock(ServerDeletionService::class);
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
        $this->daemonServerRepository->shouldReceive('create')->with(['test' => 'struct'])->once();
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
     */
    public function testExceptionShouldBeThrownIfTheRequestFails()
    {
        $this->expectException(DaemonConnectionException::class);

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

        $this->connection->expects('commit')->withNoArgs();

        $this->daemonServerRepository->shouldReceive('setServer')->with($model)->once()->andThrow(
            new DaemonConnectionException(
                new ConnectException('', new Request('GET', 'test'))
            )
        );

        $this->serverDeletionService->expects('withForce')->with(true)->andReturnSelf();
        $this->serverDeletionService->expects('handle')->with($model);

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
            $this->serverDeletionService,
            $this->repository,
            $this->serverVariableRepository,
            $this->validatorService
        );
    }
}
