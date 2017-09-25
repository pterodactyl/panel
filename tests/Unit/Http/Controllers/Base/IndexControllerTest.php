<?php
/*
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

namespace Tests\Unit\Http\Controllers\Base;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Tests\Assertions\ControllerAssertionsTrait;
use Pterodactyl\Http\Controllers\Base\IndexController;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class IndexControllerTest extends TestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \Pterodactyl\Http\Controllers\Base\IndexController
     */
    protected $controller;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $daemonRepository;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService|\Mockery\Mock
     */
    protected $keyProviderService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Illuminate\Http\Request|\Mockery\Mock
     */
    protected $request;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->daemonRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->keyProviderService = m::mock(DaemonKeyProviderService::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->request = m::mock(Request::class);

        $this->controller = new IndexController($this->keyProviderService, $this->daemonRepository, $this->repository);
    }

    /**
     * Test the index controller.
     */
    public function testIndexController()
    {
        $model = factory(User::class)->make();

        $this->request->shouldReceive('user')->withNoArgs()->andReturn($model);
        $this->request->shouldReceive('input')->with('query')->once()->andReturn('searchTerm');
        $this->repository->shouldReceive('search')->with('searchTerm')->once()->andReturnSelf()
            ->shouldReceive('filterUserAccessServers')->with(
                $model->id, $model->root_admin, 'all', ['user']
            )->once()->andReturn(['test']);

        $response = $this->controller->getIndex($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.index', $response);
        $this->assertViewHasKey('servers', $response);
        $this->assertViewKeyEquals('servers', ['test'], $response);
    }

    /**
     * Test the status controller.
     */
    public function testStatusController()
    {
        $user = factory(User::class)->make();
        $server = factory(Server::class)->make(['suspended' => 0, 'installed' => 1]);

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($user);
        $this->repository->shouldReceive('findFirstWhere')->with([['uuidShort', '=', $server->uuidShort]])->once()->andReturn($server);
        $this->keyProviderService->shouldReceive('handle')->with($server->id, $user->id)->once()->andReturn('test123');

        $this->daemonRepository->shouldReceive('setNode')->with($server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($server->uuid)->once()->andReturnSelf()
            ->shouldReceive('setAccessToken')->with('test123')->once()->andReturnSelf()
            ->shouldReceive('details')->withNoArgs()->once()->andReturnSelf();

        $this->daemonRepository->shouldReceive('getBody')->withNoArgs()->once()->andReturn('["test"]');

        $response = $this->controller->status($this->request, $server->uuidShort);
        $this->assertIsJsonResponse($response);
        $this->assertResponseJsonEquals(['test'], $response);
    }

    /**
     * Test the status controller if a server is not installed.
     */
    public function testStatusControllerWhenServerNotInstalled()
    {
        $user = factory(User::class)->make();
        $server = factory(Server::class)->make(['suspended' => 0, 'installed' => 0]);

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($user);
        $this->repository->shouldReceive('findFirstWhere')->with([['uuidShort', '=', $server->uuidShort]])->once()->andReturn($server);
        $this->keyProviderService->shouldReceive('handle')->with($server->id, $user->id)->once()->andReturn('test123');

        $response = $this->controller->status($this->request, $server->uuidShort);
        $this->assertIsJsonResponse($response);
        $this->assertResponseCodeEquals(200, $response);
        $this->assertResponseJsonEquals(['status' => 20], $response);
    }

    /**
     * Test the status controller when a server is suspended.
     */
    public function testStatusControllerWhenServerIsSuspended()
    {
        $user = factory(User::class)->make();
        $server = factory(Server::class)->make(['suspended' => 1, 'installed' => 1]);

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($user);
        $this->repository->shouldReceive('findFirstWhere')->with([['uuidShort', '=', $server->uuidShort]])->once()->andReturn($server);
        $this->keyProviderService->shouldReceive('handle')->with($server->id, $user->id)->once()->andReturn('test123');

        $response = $this->controller->status($this->request, $server->uuidShort);
        $this->assertIsJsonResponse($response);
        $this->assertResponseCodeEquals(200, $response);
        $this->assertResponseJsonEquals(['status' => 30], $response);
    }
}
