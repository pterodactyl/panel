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

namespace Tests\Unit\Http\Controllers\Base;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Prologue\Alerts\AlertsMessageBag;
use Tests\Assertions\ControllerAssertionsTrait;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Http\Controllers\Base\APIController;
use Pterodactyl\Http\Requests\Base\ApiKeyFormRequest;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class APIControllerTest extends TestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Http\Controllers\Base\APIController
     */
    protected $controller;

    /**
     * @var \Pterodactyl\Services\Api\KeyCreationService
     */
    protected $keyService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->alert = m::mock(AlertsMessageBag::class);
        $this->keyService = m::mock(KeyCreationService::class);
        $this->repository = m::mock(ApiKeyRepositoryInterface::class);
        $this->request = m::mock(Request::class);

        $this->controller = new APIController($this->alert, $this->repository, $this->keyService);
    }

    /**
     * Test the index controller.
     */
    public function testIndexController()
    {
        $model = factory(User::class)->make();

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($model);
        $this->repository->shouldReceive('findWhere')->with([['user_id', '=', $model->id]])->once()->andReturn(['testkeys']);

        $response = $this->controller->index($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.api.index', $response);
        $this->assertViewHasKey('keys', $response);
        $this->assertViewKeyEquals('keys', ['testkeys'], $response);
    }

    /**
     * Test the create API view controller.
     *
     * @dataProvider rootAdminDataProvider
     */
    public function testCreateController($admin)
    {
        $model = factory(User::class)->make(['root_admin' => $admin]);
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($model);

        $response = $this->controller->create($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.api.new', $response);
        $this->assertViewHasKey('permissions.user', $response);
        $this->assertViewHasKey('permissions.admin', $response);

        if ($admin) {
            $this->assertViewKeyNotEquals('permissions.admin', null, $response);
        } else {
            $this->assertViewKeyEquals('permissions.admin', null, $response);
        }
    }

    /**
     * Test the store functionality for a non-admin user.
     *
     * @dataProvider rootAdminDataProvider
     */
    public function testStoreController($admin)
    {
        $this->request = m::mock(ApiKeyFormRequest::class);
        $model = factory(User::class)->make(['root_admin' => $admin]);

        if ($admin) {
            $this->request->shouldReceive('input')->with('admin_permissions', [])->once()->andReturn(['admin.permission']);
        }

        $this->request->shouldReceive('user')->withNoArgs()->andReturn($model);
        $this->request->shouldReceive('input')->with('allowed_ips')->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('memo')->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('permissions', [])->once()->andReturn(['test.permission']);

        $this->keyService->shouldReceive('handle')->with([
            'user_id' => $model->id,
            'allowed_ips' => null,
            'memo' => null,
        ], ['test.permission'], ($admin) ? ['admin.permission'] : [])->once()->andReturn('testToken');

        $this->alert->shouldReceive('success')->with(trans('base.api.index.keypair_created', ['token' => 'testToken']))->once()->andReturnSelf()
            ->shouldReceive('flash')->withNoArgs()->once()->andReturnNull();

        $response = $this->controller->store($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account.api', $response);
    }

    /**
     * Test the API key revocation controller.
     */
    public function testRevokeController()
    {
        $model = factory(User::class)->make();
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($model);

        $this->repository->shouldReceive('deleteWhere')->with([
            ['user_id', '=', $model->id],
            ['public', '=', 'testKey123'],
        ])->once()->andReturnNull();

        $response = $this->controller->revoke($this->request, 'testKey123');
        $this->assertIsResponse($response);
        $this->assertEmpty($response->getContent());
        $this->assertResponseCodeEquals(204, $response);
    }

    /**
     * Data provider to determine if a user is a root admin.
     *
     * @return array
     */
    public function rootAdminDataProvider()
    {
        return [[0], [1]];
    }
}
