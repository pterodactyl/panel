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

namespace Tests\Unit\Services\Subusers;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\Writer;
use Mockery as m;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Subusers\SubuserDeletionService;
use Tests\TestCase;
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

        $this->repository->shouldReceive('getWithServerAndPermissions')->with($subuser->id)->once()->andReturn($subuser);
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

        $this->repository->shouldReceive('getWithServerAndPermissions')->with($subuser->id)->once()->andReturn($subuser);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($subuser->id)->once()->andReturn(1);

        $this->daemonRepository->shouldReceive('setNode->setAccessServer->setSubuserKey')->once()->andThrow($this->exception);

        $this->connection->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();
        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();

        try {
            $this->service->handle($subuser->id);
        } catch (DisplayException $exception) {
            $this->assertEquals(trans('admin/exceptions.daemon_connection_failed', ['code' => 'E_CONN_REFUSED']), $exception->getMessage());
        }
    }

}
