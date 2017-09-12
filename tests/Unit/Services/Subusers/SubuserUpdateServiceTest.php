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

use Mockery as m;
use Tests\TestCase;
use Illuminate\Log\Writer;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Subusers\SubuserUpdateService;
use Pterodactyl\Services\Subusers\PermissionCreationService;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Contracts\Repository\PermissionRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class SubuserUpdateServiceTest extends TestCase
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
     * @var \Pterodactyl\Contracts\Repository\PermissionRepositoryInterface
     */
    protected $permissionRepository;

    /**
     * @var \Pterodactyl\Services\Subusers\PermissionCreationService
     */
    protected $permissionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserUpdateService
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
        $this->permissionRepository = m::mock(PermissionRepositoryInterface::class);
        $this->permissionService = m::mock(PermissionCreationService::class);
        $this->repository = m::mock(SubuserRepositoryInterface::class);
        $this->writer = m::mock(Writer::class);

        $this->service = new SubuserUpdateService(
            $this->connection,
            $this->daemonRepository,
            $this->permissionService,
            $this->permissionRepository,
            $this->repository,
            $this->writer
        );
    }

    /**
     * Test that permissions are updated in the database.
     */
    public function testPermissionsAreUpdated()
    {
        $subuser = factory(Subuser::class)->make();
        $subuser->server = factory(Server::class)->make();

        $this->repository->shouldReceive('getWithServer')->with($subuser->id)->once()->andReturn($subuser);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->permissionRepository->shouldReceive('deleteWhere')->with([
            ['subuser_id', '=', $subuser->id],
        ])->once()->andReturnNull();
        $this->permissionService->shouldReceive('handle')->with($subuser->id, ['some-permission'])->once()->andReturn(['test:1', 'test:2']);

        $this->daemonRepository->shouldReceive('setNode')->with($subuser->server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($subuser->server->uuid)->once()->andReturnSelf()
            ->shouldReceive('setSubuserKey')->with($subuser->daemonSecret, ['test:1', 'test:2'])->once()->andReturnNull();

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($subuser->id, ['some-permission']);
    }

    /**
     * Test that an exception is thrown if the daemon connection fails.
     */
    public function testExceptionIsThrownIfDaemonConnectionFails()
    {
        $subuser = factory(Subuser::class)->make();
        $subuser->server = factory(Server::class)->make();

        $this->repository->shouldReceive('getWithServer')->with($subuser->id)->once()->andReturn($subuser);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->permissionRepository->shouldReceive('deleteWhere')->with([
            ['subuser_id', '=', $subuser->id],
        ])->once()->andReturnNull();
        $this->permissionService->shouldReceive('handle')->with($subuser->id, [])->once()->andReturn([]);

        $this->daemonRepository->shouldReceive('setNode')->once()->andThrow($this->exception);
        $this->connection->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();
        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();

        try {
            $this->service->handle($subuser->id, []);
        } catch (DisplayException $exception) {
            $this->assertEquals(trans('exceptions.daemon_connection_failed', ['code' => 'E_CONN_REFUSED']), $exception->getMessage());
        }
    }
}
