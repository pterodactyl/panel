<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Log\Writer;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use Tests\Traits\MocksRequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ServerDeletionServiceTest extends TestCase
{
    use MocksRequestException;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
     */
    private $daemonServerRepository;

    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService|\Mockery\Mock
     */
    private $databaseManagementService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface|\Mockery\Mock
     */
    private $databaseRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Illuminate\Log\Writer|\Mockery\Mock
     */
    private $writer;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->daemonServerRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->databaseRepository = m::mock(DatabaseRepositoryInterface::class);
        $this->databaseManagementService = m::mock(DatabaseManagementService::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->writer = m::mock(Writer::class);
    }

    /**
     * Test that a server can be force deleted by setting it in a function call.
     */
    public function testForceParameterCanBeSet()
    {
        $response = $this->getService()->withForce(true);

        $this->assertInstanceOf(ServerDeletionService::class, $response);
    }

    /**
     * Test that a server can be deleted when force is not set.
     */
    public function testServerCanBeDeletedWithoutForce()
    {
        $model = factory(Server::class)->make();

        $this->daemonServerRepository->shouldReceive('setServer')->once()->with($model)->andReturnSelf();
        $this->daemonServerRepository->shouldReceive('delete')->once()->withNoArgs()->andReturn(new Response);

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->databaseRepository->shouldReceive('setColumns')->once()->with('id')->andReturnSelf();
        $this->databaseRepository->shouldReceive('findWhere')->once()->with([
            ['server_id', '=', $model->id],
        ])->andReturn(collect([(object) ['id' => 50]]));

        $this->databaseManagementService->shouldReceive('delete')->once()->with(50)->andReturnNull();
        $this->repository->shouldReceive('delete')->once()->with($model->id)->andReturn(1);
        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $this->getService()->handle($model);
    }

    /**
     * Test that a server is deleted when force is set.
     */
    public function testServerShouldBeDeletedEvenWhenFailureOccursIfForceIsSet()
    {
        $this->configureExceptionMock();
        $model = factory(Server::class)->make();

        $this->daemonServerRepository->shouldReceive('setServer')->once()->with($model)->andReturnSelf();
        $this->daemonServerRepository->shouldReceive('delete')->once()->withNoArgs()->andThrow($this->getExceptionMock());

        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->databaseRepository->shouldReceive('setColumns')->with('id')->once()->andReturnSelf();
        $this->databaseRepository->shouldReceive('findWhere')->with([
            ['server_id', '=', $model->id],
        ])->once()->andReturn(collect([(object) ['id' => 50]]));

        $this->databaseManagementService->shouldReceive('delete')->with(50)->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($model->id)->once()->andReturn(1);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->getService()->withForce()->handle($model);
    }

    /**
     * Test that an exception is thrown if a server cannot be deleted from the node and force is not set.
     *
     * @expectedException \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function testExceptionShouldBeThrownIfDaemonReturnsAnErrorAndForceIsNotSet()
    {
        $this->configureExceptionMock();
        $model = factory(Server::class)->make();

        $this->daemonServerRepository->shouldReceive('setServer->delete')->once()->andThrow($this->getExceptionMock());

        $this->getService()->handle($model);
    }

    /**
     * Return an instance of the class with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Servers\ServerDeletionService
     */
    private function getService(): ServerDeletionService
    {
        return new ServerDeletionService(
            $this->connection,
            $this->daemonServerRepository,
            $this->databaseRepository,
            $this->databaseManagementService,
            $this->repository,
            $this->writer
        );
    }
}
