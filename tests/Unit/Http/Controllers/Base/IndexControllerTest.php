<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Http\Controllers\Base;

use Mockery as m;
use Pterodactyl\Models\User;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Tests\Assertions\ControllerAssertionsTrait;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Http\Controllers\Base\IndexController;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class IndexControllerTest extends ControllerTestCase
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
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->daemonRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->keyProviderService = m::mock(DaemonKeyProviderService::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);

        $this->controller = new IndexController($this->keyProviderService, $this->daemonRepository, $this->repository);
    }

    /**
     * Test the index controller.
     */
    public function testIndexController()
    {
        $paginator = m::mock(LengthAwarePaginator::class);
        $model = $this->generateRequestUserModel();

        $this->request->shouldReceive('input')->with('query')->once()->andReturn('searchTerm');
        $this->repository->shouldReceive('setSearchTerm')->with('searchTerm')->once()->andReturnSelf()
            ->shouldReceive('filterUserAccessServers')->with($model, User::FILTER_LEVEL_ALL, config('pterodactyl.paginate.frontend.servers'))
            ->once()->andReturn($paginator);

        $response = $this->controller->getIndex($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.index', $response);
        $this->assertViewHasKey('servers', $response);
        $this->assertViewKeyEquals('servers', $paginator, $response);
    }

    /**
     * Test the status controller.
     */
    public function testStatusController()
    {
        $user = $this->generateRequestUserModel();
        $server = factory(Server::class)->make(['suspended' => 0, 'installed' => 1]);
        $psrResponse = new Response;

        $this->repository->shouldReceive('findFirstWhere')->with([['uuidShort', '=', $server->uuidShort]])->once()->andReturn($server);
        $this->keyProviderService->shouldReceive('handle')->with($server, $user)->once()->andReturn('test123');

        $this->daemonRepository->shouldReceive('setServer')->with($server)->once()->andReturnSelf()
            ->shouldReceive('setToken')->with('test123')->once()->andReturnSelf()
            ->shouldReceive('details')->withNoArgs()->once()->andReturn($psrResponse);

        $response = $this->controller->status($this->request, $server->uuidShort);
        $this->assertIsJsonResponse($response);
        $this->assertResponseJsonEquals(json_encode($psrResponse->getBody()), $response);
    }

    /**
     * Test the status controller if a server is not installed.
     */
    public function testStatusControllerWhenServerNotInstalled()
    {
        $user = $this->generateRequestUserModel();
        $server = factory(Server::class)->make(['suspended' => 0, 'installed' => 0]);

        $this->repository->shouldReceive('findFirstWhere')->with([['uuidShort', '=', $server->uuidShort]])->once()->andReturn($server);
        $this->keyProviderService->shouldReceive('handle')->with($server, $user)->once()->andReturn('test123');

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
        $this->keyProviderService->shouldReceive('handle')->with($server, $user)->once()->andReturn('test123');

        $response = $this->controller->status($this->request, $server->uuidShort);
        $this->assertIsJsonResponse($response);
        $this->assertResponseCodeEquals(200, $response);
        $this->assertResponseJsonEquals(['status' => 30], $response);
    }

    /**
     * Test the status controller with a ServerConnectionException.
     */
    public function testStatusControllerWithServerConnectionException()
    {
        $user = factory(User::class)->make();
        $server = factory(Server::class)->make(['suspended' => 0, 'installed' => 1]);

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($user);
        $this->repository->shouldReceive('findFirstWhere')->with([['uuidShort', '=', $server->uuidShort]])->once()->andReturn($server);
        $this->keyProviderService->shouldReceive('handle')->with($server, $user)->once()->andReturn('test123');

        $this->daemonRepository->shouldReceive('setServer')->with($server)->once()->andReturnSelf()
            ->shouldReceive('setToken')->with('test123')->once()->andReturnSelf()
            ->shouldReceive('details')->withNoArgs()->once()->andThrow(new ConnectException('bad connection', new ServerRequest('', '')));

        $this->expectExceptionObject(new HttpException(500, 'bad connection'));
        $this->controller->status($this->request, $server->uuidShort);
    }

    /**
     * Test the status controller with a RequestException.
     */
    public function testStatusControllerWithRequestException()
    {
        $user = factory(User::class)->make();
        $server = factory(Server::class)->make(['suspended' => 0, 'installed' => 1]);

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($user);
        $this->repository->shouldReceive('findFirstWhere')->with([['uuidShort', '=', $server->uuidShort]])->once()->andReturn($server);
        $this->keyProviderService->shouldReceive('handle')->with($server, $user)->once()->andReturn('test123');

        $this->daemonRepository->shouldReceive('setServer')->with($server)->once()->andReturnSelf()
            ->shouldReceive('setToken')->with('test123')->once()->andReturnSelf()
            ->shouldReceive('details')->withNoArgs()->once()->andThrow(new RequestException('bad request', new ServerRequest('', '')));

        $this->expectExceptionObject(new HttpException(500, 'bad request'));
        $this->controller->status($this->request, $server->uuidShort);
    }
}
