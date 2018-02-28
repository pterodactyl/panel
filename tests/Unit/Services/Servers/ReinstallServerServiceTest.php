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
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ReinstallServerServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $database;

    /**
     * @var \GuzzleHttp\Exception\RequestException
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Models\Server
     */
    protected $server;

    /**
     * @var \Pterodactyl\Services\Servers\ReinstallServerService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->daemonServerRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->database = m::mock(ConnectionInterface::class);
        $this->exception = m::mock(RequestException::class)->makePartial();
        $this->repository = m::mock(ServerRepositoryInterface::class);

        $this->server = factory(Server::class)->make(['node_id' => 1]);

        $this->service = new ReinstallServerService(
            $this->database,
            $this->daemonServerRepository,
            $this->repository
        );
    }

    /**
     * Test that a server is reinstalled when it's model is passed to the function.
     */
    public function testServerShouldBeReinstalledWhenModelIsPassed()
    {
        $this->repository->shouldNotReceive('find');

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel->update')->with($this->server->id, [
            'installed' => 0,
        ], true, true)->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setServer')->with($this->server)->once()->andReturnSelf()
            ->shouldReceive('reinstall')->withNoArgs()->once()->andReturn(new Response);
        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->reinstall($this->server);
    }

    /**
     * Test that a server is reinstalled when the ID of the server is passed to the function.
     */
    public function testServerShouldBeReinstalledWhenServerIdIsPassed()
    {
        $this->repository->shouldReceive('find')->with($this->server->id)->once()->andReturn($this->server);

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel->update')->with($this->server->id, [
            'installed' => 0,
        ], true, true)->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setServer')->with($this->server)->once()->andReturnSelf()
            ->shouldReceive('reinstall')->withNoArgs()->once()->andReturn(new Response);
        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->reinstall($this->server->id);
    }

    /**
     * Test that an exception thrown by guzzle is rendered as a displayable exception.
     *
     * @expectedException \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function testExceptionThrownByGuzzleShouldBeReRenderedAsDisplayable()
    {
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel->update')->with($this->server->id, [
            'installed' => 0,
        ], true, true)->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setServer')->with($this->server)->once()->andThrow($this->exception);

        $this->service->reinstall($this->server);
    }

    /**
     * Test that an exception thrown by something other than guzzle is not transformed to a displayable.
     *
     * @expectedException \Exception
     */
    public function testExceptionNotThrownByGuzzleShouldNotBeTransformedToDisplayable()
    {
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel->update')->with($this->server->id, [
            'installed' => 0,
        ], true, true)->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setServer')->with($this->server)->once()->andThrow(new Exception());

        $this->service->reinstall($this->server);
    }
}
