<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Servers;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Illuminate\Log\Writer;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ServerDeletionServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    protected $databaseManagementService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    protected $databaseRepository;

    /**
     * @var \GuzzleHttp\Exception\RequestException
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Models\Server
     */
    protected $model;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Servers\ServerDeletionService
     */
    protected $service;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

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
        $this->exception = m::mock(RequestException::class);
        $this->model = factory(Server::class)->make();
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->writer = m::mock(Writer::class);

        $this->service = new ServerDeletionService(
            $this->connection,
            $this->daemonServerRepository,
            $this->databaseRepository,
            $this->databaseManagementService,
            $this->repository,
            $this->writer
        );
    }

    /**
     * Test that a server can be force deleted by setting it in a function call.
     */
    public function testForceParameterCanBeSet()
    {
        $response = $this->service->withForce(true);

        $this->assertInstanceOf(ServerDeletionService::class, $response);
    }

    /**
     * Test that a server can be deleted when force is not set.
     */
    public function testServerCanBeDeletedWithoutForce()
    {
        $this->daemonServerRepository->shouldReceive('setServer')->with($this->model)->once()->andReturnSelf()
            ->shouldReceive('delete')->withNoArgs()->once()->andReturn(new Response);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->databaseRepository->shouldReceive('setColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findWhere')->with([
                ['server_id', '=', $this->model->id],
            ])->once()->andReturn(collect([(object) ['id' => 50]]));

        $this->databaseManagementService->shouldReceive('delete')->with(50)->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($this->model->id)->once()->andReturn(1);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($this->model);
    }

    /**
     * Test that a server is deleted when force is set.
     */
    public function testServerShouldBeDeletedEvenWhenFailureOccursIfForceIsSet()
    {
        $this->daemonServerRepository->shouldReceive('setServer')->with($this->model)->once()->andReturnSelf()
            ->shouldReceive('delete')->withNoArgs()->once()->andThrow($this->exception);

        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();
        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->databaseRepository->shouldReceive('setColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findWhere')->with([
                ['server_id', '=', $this->model->id],
            ])->once()->andReturn(collect([(object) ['id' => 50]]));

        $this->databaseManagementService->shouldReceive('delete')->with(50)->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($this->model->id)->once()->andReturn(1);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->withForce()->handle($this->model);
    }

    /**
     * Test that an exception is thrown if a server cannot be deleted from the node and force is not set.
     */
    public function testExceptionShouldBeThrownIfDaemonReturnsAnErrorAndForceIsNotSet()
    {
        $this->daemonServerRepository->shouldReceive('setServer->delete')->once()->andThrow($this->exception);
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();
        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();

        try {
            $this->service->handle($this->model);
        } catch (Exception $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(trans('admin/server.exceptions.daemon_exception', [
                'code' => 'E_CONN_REFUSED',
            ]), $exception->getMessage());
        }
    }

    /**
     * Test that an integer can be passed in place of the Server model.
     */
    public function testIntegerCanBePassedInPlaceOfServerModel()
    {
        $this->repository->shouldReceive('setColumns')->with(['id', 'node_id', 'uuid'])->once()->andReturnSelf()
            ->shouldReceive('find')->with($this->model->id)->once()->andReturn($this->model);

        $this->daemonServerRepository->shouldReceive('setServer')->with($this->model)->once()->andReturnSelf()
            ->shouldReceive('delete')->withNoArgs()->once()->andReturn(new Response);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->databaseRepository->shouldReceive('setColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findWhere')->with([
                ['server_id', '=', $this->model->id],
            ])->once()->andReturn(collect([(object) ['id' => 50]]));

        $this->databaseManagementService->shouldReceive('delete')->with(50)->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($this->model->id)->once()->andReturn(1);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($this->model->id);
    }
}
