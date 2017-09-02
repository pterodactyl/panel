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

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Mockery as m;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Contracts\Repository\SessionRepositoryInterface;
use Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid;
use Pterodactyl\Http\Controllers\Base\SecurityController;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Users\ToggleTwoFactorService;
use Pterodactyl\Services\Users\TwoFactorSetupService;
use Tests\Assertions\ControllerAssertionsTrait;
use Tests\TestCase;

class SecurityControllerTest extends TestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Http\Controllers\Base\SecurityController
     */
    protected $controller;

    /**
     * @var \Pterodactyl\Contracts\Repository\SessionRepositoryInterface
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
     * @var \Pterodactyl\Services\Users\ToggleTwoFactorService
     */
    protected $toggleTwoFactorService;

    /**
     * @var \Pterodactyl\Services\Users\TwoFactorSetupService
     */
    protected $twoFactorSetupService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->alert = m::mock(AlertsMessageBag::class);
        $this->config = m::mock(Repository::class);
        $this->repository = m::mock(SessionRepositoryInterface::class);
        $this->request = m::mock(Request::class);
        $this->session = m::mock(Session::class);
        $this->toggleTwoFactorService = m::mock(ToggleTwoFactorService::class);
        $this->twoFactorSetupService = m::mock(TwoFactorSetupService::class);

        $this->controller = new SecurityController(
            $this->alert,
            $this->config,
            $this->session,
            $this->repository,
            $this->toggleTwoFactorService,
            $this->twoFactorSetupService
        );
    }

    /**
     * Test the index controller when using a database driver.
     */
    public function testIndexControllerWithDatabaseDriver()
    {
        $model = factory(User::class)->make();

        $this->config->shouldReceive('get')->with('session.driver')->once()->andReturn('database');
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($model);
        $this->repository->shouldReceive('getUserSessions')->with($model->id)->once()->andReturn(['sessions']);

        $response = $this->controller->index($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.security', $response);
        $this->assertViewHasKey('sessions', $response);
        $this->assertViewKeyEquals('sessions', ['sessions'], $response);
    }

    /**
     * Test the index controller when not using the database driver.
     */
    public function testIndexControllerWithoutDatabaseDriver()
    {
        $this->config->shouldReceive('get')->with('session.driver')->once()->andReturn('redis');

        $response = $this->controller->index($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.security', $response);
        $this->assertViewHasKey('sessions', $response);
        $this->assertViewKeyEquals('sessions', null, $response);
    }

    /**
     * Test TOTP generation controller.
     */
    public function testGenerateTotpController()
    {
        $model = factory(User::class)->make();

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($model);
        $this->twoFactorSetupService->shouldReceive('handle')->with($model)->once()->andReturn(['string']);

        $response = $this->controller->generateTotp($this->request);
        $this->assertIsJsonResponse($response);
        $this->assertResponseJsonEquals(['string'], $response);
    }

    /**
     * Test the disable totp controller when no exception is thrown by the service.
     */
    public function testDisableTotpControllerSuccess()
    {
        $model = factory(User::class)->make();

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($model);
        $this->request->shouldReceive('input')->with('token')->once()->andReturn('testToken');
        $this->toggleTwoFactorService->shouldReceive('handle')->with($model, 'testToken', false)->once()->andReturnNull();

        $response = $this->controller->disableTotp($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRouteRedirectEquals('account.security', $response);
    }

    /**
     * Test the disable totp controller when an exception is thrown by the service.
     */
    public function testDisableTotpControllerWhenExceptionIsThrown()
    {
        $model = factory(User::class)->make();

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($model);
        $this->request->shouldReceive('input')->with('token')->once()->andReturn('testToken');
        $this->toggleTwoFactorService->shouldReceive('handle')->with($model, 'testToken', false)->once()
            ->andThrow(new TwoFactorAuthenticationTokenInvalid);
        $this->alert->shouldReceive('danger')->with(trans('base.security.2fa_disable_error'))->once()->andReturnSelf()
            ->shouldReceive('flash')->withNoArgs()->once()->andReturnNull();

        $response = $this->controller->disableTotp($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRouteRedirectEquals('account.security', $response);
    }

    /**
     * Test the revoke controller.
     */
    public function testRevokeController()
    {
        $model = factory(User::class)->make();

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn($model);
        $this->repository->shouldReceive('deleteUserSession')->with($model->id, 123)->once()->andReturnNull();

        $response = $this->controller->revoke($this->request, 123);
        $this->assertIsRedirectResponse($response);
        $this->assertRouteRedirectEquals('account.security', $response);
    }
}
