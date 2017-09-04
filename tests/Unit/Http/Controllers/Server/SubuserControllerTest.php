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

namespace Tests\Unit\Http\Controllers\Server;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Mockery as m;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Http\Controllers\Server\SubuserController;
use Pterodactyl\Models\Permission;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Subusers\SubuserCreationService;
use Pterodactyl\Services\Subusers\SubuserDeletionService;
use Pterodactyl\Services\Subusers\SubuserUpdateService;
use Tests\Assertions\ControllerAssertionsTrait;
use Tests\TestCase;

class SubuserControllerTest extends TestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Http\Controllers\Server\SubuserController
     */
    protected $controller;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserCreationService
     */
    protected $subuserCreationService;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserDeletionService
     */
    protected $subuserDeletionService;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserUpdateService
     */
    protected $subuserUpdateService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->alert = m::mock(AlertsMessageBag::class);
        $this->repository = m::mock(SubuserRepositoryInterface::class);
        $this->request = m::mock(Request::class);
        $this->session = m::mock(Session::class);
        $this->subuserCreationService = m::mock(SubuserCreationService::class);
        $this->subuserDeletionService = m::mock(SubuserDeletionService::class);
        $this->subuserUpdateService = m::mock(SubuserUpdateService::class);

        $this->controller = m::mock(SubuserController::class, [
            $this->alert,
            $this->session,
            $this->subuserCreationService,
            $this->subuserDeletionService,
            $this->repository,
            $this->subuserUpdateService,
        ])->makePartial();
    }

    /*
     * Test index controller.
     */
    public function testIndexController()
    {
        $server = factory(Server::class)->make();

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('list-subusers', $server)->once()->andReturnNull();
        $this->controller->shouldReceive('injectJavascript')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('findWhere')->with([['server_id', '=', $server->id]])->once()->andReturn([]);

        $response = $this->controller->index();
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('server.users.index', $response);
        $this->assertViewHasKey('subusers', $response);
    }

    /**
     * Test view controller.
     */
    public function testViewController()
    {
        $subuser = factory(Subuser::class)->make([
            'permissions' => collect([
                (object) ['permission' => 'some.permission'],
                (object) ['permission' => 'another.permission'],
            ]),
        ]);
        $server = factory(Server::class)->make();

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('view-subuser', $server)->once()->andReturnNull();
        $this->repository->shouldReceive('getWithPermissions')->with(1234)->once()->andReturn($subuser);
        $this->controller->shouldReceive('injectJavascript')->withNoArgs()->once()->andReturnNull();

        $response = $this->controller->view($server->uuid, 1234);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('server.users.view', $response);
        $this->assertViewHasKey('subuser', $response);
        $this->assertViewHasKey('permlist', $response);
        $this->assertViewHasKey('permissions', $response);
        $this->assertViewKeyEquals('subuser', $subuser, $response);
        $this->assertViewKeyEquals('permlist', Permission::getPermissions(), $response);
        $this->assertViewKeyEquals('permissions', collect([
            'some.permission' => true,
            'another.permission' => true,
        ]), $response);
    }

    /**
     * Test the update controller.
     */
    public function testUpdateController()
    {
        $server = factory(Server::class)->make();

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('edit-subuser', $server)->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('permissions', [])->once()->andReturn(['some.permission']);
        $this->subuserUpdateService->shouldReceive('handle')->with(1234, ['some.permission'])->once()->andReturnNull();
        $this->alert->shouldReceive('success')->with(trans('server.users.user_updated'))->once()->andReturnSelf()
            ->shouldReceive('flash')->withNoArgs()->once()->andReturnNull();

        $response = $this->controller->update($this->request, $server->uuid, 1234);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('server.subusers.view', $response, ['uuid' => $server->uuid, 'id' => 1234]);
    }

    /**
     * Test the create controller.
     */
    public function testCreateController()
    {
        $server = factory(Server::class)->make();

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('create-subuser', $server)->once()->andReturnNull();
        $this->controller->shouldReceive('injectJavascript')->withNoArgs()->once()->andReturnNull();

        $response = $this->controller->create();
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('server.users.new', $response);
        $this->assertViewHasKey('permissions', $response);
        $this->assertViewKeyEquals('permissions', Permission::getPermissions(), $response);
    }

    /**
     * Test the store controller.
     */
    public function testStoreController()
    {
        $server = factory(Server::class)->make();
        $subuser = factory(Subuser::class)->make();

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('create-subuser', $server)->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('email')->once()->andReturn('user@test.com');
        $this->request->shouldReceive('input')->with('permissions', [])->once()->andReturn(['some.permission']);
        $this->subuserCreationService->shouldReceive('handle')->with($server, 'user@test.com', ['some.permission'])->once()->andReturn($subuser);
        $this->alert->shouldReceive('success')->with(trans('server.users.user_assigned'))->once()->andReturnSelf()
            ->shouldReceive('flash')->withNoArgs()->once()->andReturnNull();

        $response = $this->controller->store($this->request, $server->uuid);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('server.subusers.view', $response, ['uuid' => $server->uuid, 'id' => $subuser->id]);
    }

    /**
     * Test the delete controller.
     */
    public function testDeleteController()
    {
        $server = factory(Server::class)->make();

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('delete-subuser', $server)->once()->andReturnNull();
        $this->subuserDeletionService->shouldReceive('handle')->with(1234)->once()->andReturnNull();

        $response = $this->controller->delete($server->uuid, 1234);
        $this->assertIsResponse($response);
        $this->assertResponseCodeEquals(204, $response);
    }
}
