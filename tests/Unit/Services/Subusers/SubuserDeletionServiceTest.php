<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Subusers;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Log\Writer;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Subusers\SubuserDeletionService;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class SubuserDeletionServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonRepository;

    /**
     * @var \GuzzleHttp\Exception\RequestException
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserDeletionService
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
        $this->daemonRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->exception = m::mock(RequestException::class);
        $this->repository = m::mock(SubuserRepositoryInterface::class);
        $this->writer = m::mock(Writer::class);

        $this->service = new SubuserDeletionService(
            $this->connection,
            $this->daemonRepository,
            $this->repository,
            $this->writer
        );
    }

    /**
     * Test that a subuser is deleted correctly.
     */
    public function testSubuserIsDeleted()
    {
        $subuser = factory(Subuser::class)->make();
        $subuser->server = factory(Server::class)->make();

        $this->repository->shouldReceive('getWithServer')->with($subuser->id)->once()->andReturn($subuser);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($subuser->id)->once()->andReturn(1);

        $this->daemonRepository->shouldReceive('setNode')->with($subuser->server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($subuser->server->uuid)->once()->andReturnSelf()
            ->shouldReceive('setSubuserKey')->with($subuser->daemonSecret, [])->once()->andReturnNull();

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($subuser->id);
        $this->assertEquals(1, $response);
    }

    /**
     * Test that an exception caused by the daemon is properly handled.
     */
    public function testExceptionIsThrownIfDaemonConnectionFails()
    {
        $subuser = factory(Subuser::class)->make();
        $subuser->server = factory(Server::class)->make();

        $this->repository->shouldReceive('getWithServer')->with($subuser->id)->once()->andReturn($subuser);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($subuser->id)->once()->andReturn(1);

        $this->daemonRepository->shouldReceive('setNode->setAccessServer->setSubuserKey')->once()->andThrow($this->exception);

        $this->connection->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();
        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();

        try {
            $this->service->handle($subuser->id);
        } catch (DisplayException $exception) {
            $this->assertEquals(trans('exceptions.daemon_connection_failed', ['code' => 'E_CONN_REFUSED']), $exception->getMessage());
        }
    }
}
