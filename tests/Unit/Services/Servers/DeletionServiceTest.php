<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Tests\Unit\Services\Servers;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Illuminate\Log\Writer;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Servers\DeletionService;
use Pterodactyl\Services\Database\DatabaseManagementService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class DeletionServiceTest extends TestCase
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
     * @var \Pterodactyl\Services\Database\DatabaseManagementService
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
     * @var \Pterodactyl\Services\Servers\DeletionService
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

        $this->service = new DeletionService(
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

        $this->assertInstanceOf(DeletionService::class, $response);
    }

    /**
     * Test that a server can be deleted when force is not set.
     */
    public function testServerCanBeDeletedWithoutForce()
    {
        $this->daemonServerRepository->shouldReceive('setNode')->with($this->model->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($this->model->uuid)->once()->andReturnSelf()
            ->shouldReceive('delete')->withNoArgs()->once()->andReturnNull();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->databaseRepository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findWhere')->with([
                ['server_id', '=', $this->model->id],
            ])->once()->andReturn(collect([(object) ['id' => 50]]));

        $this->databaseManagementService->shouldReceive('delete')->with(50)->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($this->model->id)->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($this->model);
    }

    /**
     * Test that a server is deleted when force is set.
     */
    public function testServerShouldBeDeletedEvenWhenFailureOccursIfForceIsSet()
    {
        $this->daemonServerRepository->shouldReceive('setNode')->with($this->model->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($this->model->uuid)->once()->andReturnSelf()
            ->shouldReceive('delete')->withNoArgs()->once()->andThrow($this->exception);

        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();
        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->databaseRepository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findWhere')->with([
                ['server_id', '=', $this->model->id],
            ])->once()->andReturn(collect([(object) ['id' => 50]]));

        $this->databaseManagementService->shouldReceive('delete')->with(50)->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($this->model->id)->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->withForce()->handle($this->model);
    }

    /**
     * Test that an exception is thrown if a server cannot be deleted from the node and force is not set.
     */
    public function testExceptionShouldBeThrownIfDaemonReturnsAnErrorAndForceIsNotSet()
    {
        $this->daemonServerRepository->shouldReceive('setNode->setAccessServer->delete')->once()->andThrow($this->exception);
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
        $this->repository->shouldReceive('withColumns')->with(['id', 'node_id', 'uuid'])->once()->andReturnSelf()
            ->shouldReceive('find')->with($this->model->id)->once()->andReturn($this->model);

        $this->daemonServerRepository->shouldReceive('setNode')->with($this->model->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($this->model->uuid)->once()->andReturnSelf()
            ->shouldReceive('delete')->withNoArgs()->once()->andReturnNull();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->databaseRepository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findWhere')->with([
                ['server_id', '=', $this->model->id],
            ])->once()->andReturn(collect([(object) ['id' => 50]]));

        $this->databaseManagementService->shouldReceive('delete')->with(50)->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($this->model->id)->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($this->model->id);
    }
}
