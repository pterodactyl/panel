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
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Servers\ContainerRebuildService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ContainerRebuildServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

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
     * @var \Pterodactyl\Services\Servers\ContainerRebuildService
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

        $this->daemonServerRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->exception = m::mock(RequestException::class)->makePartial();
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->writer = m::mock(Writer::class);

        $this->server = factory(Server::class)->make(['node_id' => 1]);

        $this->service = new ContainerRebuildService(
            $this->daemonServerRepository,
            $this->repository,
            $this->writer
        );
    }

    /**
     * Test that a server is marked for rebuild when it's model is passed to the function.
     */
    public function testServerShouldBeMarkedForARebuildWhenModelIsPassed()
    {
        $this->repository->shouldNotReceive('find');
        $this->daemonServerRepository->shouldReceive('setNode')->with($this->server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($this->server->uuid)->once()->andReturnSelf()
            ->shouldReceive('rebuild')->withNoArgs()->once()->andReturnNull();

        $this->service->rebuild($this->server);
    }

    /**
     * Test that a server is marked for rebuild when the ID of the server is passed to the function.
     */
    public function testServerShouldBeMarkedForARebuildWhenServerIdIsPassed()
    {
        $this->repository->shouldReceive('find')->with($this->server->id)->once()->andReturn($this->server);

        $this->daemonServerRepository->shouldReceive('setNode')->with($this->server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($this->server->uuid)->once()->andReturnSelf()
            ->shouldReceive('rebuild')->withNoArgs()->once()->andReturnNull();

        $this->service->rebuild($this->server->id);
    }

    /**
     * Test that an exception thrown by guzzle is rendered as a displayable exception.
     */
    public function testExceptionThrownByGuzzleShouldBeReRenderedAsDisplayable()
    {
        $this->daemonServerRepository->shouldReceive('setNode')->with($this->server->node_id)
            ->once()->andThrow($this->exception);

        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('getStatusCode')->withNoArgs()->once()->andReturn(400);

        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();

        try {
            $this->service->rebuild($this->server);
        } catch (Exception $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(
                trans('admin/server.exceptions.daemon_exception', ['code' => 400]),
                $exception->getMessage()
            );
        }
    }

    /**
     * Test that an exception thrown by something other than guzzle is not transformed to a displayable.
     *
     * @expectedException \Exception
     */
    public function testExceptionNotThrownByGuzzleShouldNotBeTransformedToDisplayable()
    {
        $this->daemonServerRepository->shouldReceive('setNode')->with($this->server->node_id)
            ->once()->andThrow(new Exception());

        $this->service->rebuild($this->server);
    }
}
