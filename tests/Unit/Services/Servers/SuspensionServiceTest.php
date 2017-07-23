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
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Mockery as m;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\SuspensionService;
use Tests\TestCase;

class SuspensionServiceTest extends TestCase
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
     * @var \Pterodactyl\Services\Servers\SuspensionService
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

        $this->server = factory(Server::class)->make(['suspended' => 0, 'node_id' => 1]);

        $this->service = new SuspensionService(
            $this->database,
            $this->daemonServerRepository,
            $this->repository
        );
    }

    /**
     * Test that the function accepts an integer in place of the server model.
     *
     * @expectedException \Exception
     */
    public function testFunctionShouldAcceptAnIntegerInPlaceOfAServerModel()
    {
        $this->repository->shouldReceive('find')->with($this->server->id)->once()->andThrow(new Exception());

        $this->service->toggle($this->server->id);
    }

    /**
     * Test that no action being passed suspends a server.
     */
    public function testServerShouldBeSuspendedWhenNoActionIsPassed()
    {
        $this->server->suspended = 0;

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->server->id, ['suspended' => true])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->with($this->server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($this->server->uuid)->once()->andReturnSelf()
            ->shouldReceive('suspend')->withNoArgs()->once()->andReturnNull();
        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->assertTrue($this->service->toggle($this->server));
    }


    /**
     * Test that server is unsuspended if action=unsuspend
     */
    public function testServerShouldBeUnsuspendedWhenUnsuspendActionIsPassed()
    {
        $this->server->suspended = 1;

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->server->id, ['suspended' => false])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->with($this->server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($this->server->uuid)->once()->andReturnSelf()
            ->shouldReceive('unsuspend')->withNoArgs()->once()->andReturnNull();
        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->assertTrue($this->service->toggle($this->server, 'unsuspend'));
    }

    /**
     * Test that nothing happens if a server is already unsuspended and action=unsuspend
     */
    public function testNoActionShouldHappenIfServerIsAlreadyUnsuspendedAndActionIsUnsuspend()
    {
        $this->server->suspended = 0;

        $this->assertTrue($this->service->toggle($this->server, 'unsuspend'));
    }

    /**
     * Test that nothing happens if a server is already suspended and action=suspend
     */
    public function testNoActionShouldHappenIfServerIsAlreadySuspendedAndActionIsSuspend()
    {
        $this->server->suspended = 1;

        $this->assertTrue($this->service->toggle($this->server, 'suspend'));
    }

    /**
     * Test that an exception thrown by Guzzle is caught and transformed to a displayable exception.
     */
    public function testExceptionThrownByGuzzleShouldBeCaughtAndTransformedToDisplayable()
    {
        $this->server->suspended = 0;

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->server->id, ['suspended' => true])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->with($this->server->node_id)
            ->once()->andThrow($this->exception);

        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('getStatusCode')->withNoArgs()->once()->andReturn(400);

        try {
            $this->service->toggle($this->server);
        } catch (Exception $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(
                trans('admin/server.exceptions.daemon_exception', ['code' => 400,]), $exception->getMessage()
            );
        }
    }

    /**
     * Test that if action is not suspend or unsuspend an exception is thrown.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionShouldBeThrownIfActionIsNotValid()
    {
        $this->service->toggle($this->server, 'random');
    }
}
