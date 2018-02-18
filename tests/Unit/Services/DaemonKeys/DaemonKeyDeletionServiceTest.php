<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\DaemonKeys;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Log\Writer;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\DaemonKey;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class DaemonKeyDeletionServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $daemonRepository;

    /**
     * @var \GuzzleHttp\Exception\RequestException|\Mockery\Mock
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService
     */
    protected $service;

    /**
     * @var \Illuminate\Log\Writer|\Mockery\Mock
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
        $this->repository = m::mock(DaemonKeyRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->writer = m::mock(Writer::class);

        $this->service = new DaemonKeyDeletionService(
            $this->connection,
            $this->repository,
            $this->daemonRepository,
            $this->serverRepository,
            $this->writer
        );
    }

    /**
     * Test that a daemon key is deleted correctly.
     */
    public function testKeyIsDeleted()
    {
        $server = factory(Server::class)->make();
        $key = factory(DaemonKey::class)->make();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', 100],
            ['server_id', '=', $server->id],
        ])->once()->andReturn($key);

        $this->repository->shouldReceive('delete')->with($key->id)->once()->andReturn(1);
        $this->daemonRepository->shouldReceive('setServer')->with($server)->once()->andReturnSelf()
            ->shouldReceive('revokeAccessKey')->with($key->secret)->once()->andReturn(new Response);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($server, 100);
        $this->assertTrue(true);
    }

    /**
     * Test that a daemon key can be deleted when only a server ID is passed.
     */
    public function testKeyIsDeletedIfIdIsPassedInPlaceOfModel()
    {
        $server = factory(Server::class)->make();
        $key = factory(DaemonKey::class)->make();

        $this->serverRepository->shouldReceive('find')->with($server->id)->once()->andReturn($server);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', 100],
            ['server_id', '=', $server->id],
        ])->once()->andReturn($key);

        $this->repository->shouldReceive('delete')->with($key->id)->once()->andReturn(1);
        $this->daemonRepository->shouldReceive('setServer')->with($server)->once()->andReturnSelf()
            ->shouldReceive('revokeAccessKey')->with($key->secret)->once()->andReturn(new Response);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($server->id, 100);
        $this->assertTrue(true);
    }

    /**
     * Test that an exception is properly handled if thrown by guzzle.
     */
    public function testExceptionReturnedByGuzzleIsHandled()
    {
        $server = factory(Server::class)->make();
        $key = factory(DaemonKey::class)->make();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', 100],
            ['server_id', '=', $server->id],
        ])->once()->andReturn($key);

        $this->repository->shouldReceive('delete')->with($key->id)->once()->andReturn(1);
        $this->daemonRepository->shouldReceive('setServer')->with($server)->once()->andThrow($this->exception);
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();
        $this->connection->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();
        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();

        try {
            $this->service->handle($server, 100);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(trans('admin/server.exceptions.daemon_exception', [
                'code' => 'E_CONN_REFUSED',
            ]), $exception->getMessage());
        }
    }
}
