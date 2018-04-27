<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Services\Servers\ContainerRebuildService;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface;

class ContainerRebuildServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \GuzzleHttp\Exception\RequestException
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Models\Server
     */
    protected $server;

    /**
     * @var \Pterodactyl\Services\Servers\ContainerRebuildService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->exception = m::mock(RequestException::class)->makePartial();
        $this->repository = m::mock(ServerRepositoryInterface::class);

        $this->server = factory(Server::class)->make(['node_id' => 1]);
        $this->service = new ContainerRebuildService($this->repository);
    }

    /**
     * Test that a server is marked for rebuild.
     */
    public function testServerIsMarkedForRebuild()
    {
        $this->repository->shouldReceive('setServer')->with($this->server)->once()->andReturnSelf()
            ->shouldReceive('rebuild')->withNoArgs()->once()->andReturn(new Response);

        $this->service->handle($this->server);
    }

    /**
     * Test that an exception thrown by guzzle is rendered as a displayable exception.
     *
     * @expectedException \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function testExceptionThrownByGuzzle()
    {
        $this->repository->shouldReceive('setServer')->with($this->server)->once()->andThrow($this->exception);

        $this->service->handle($this->server);
    }
}
