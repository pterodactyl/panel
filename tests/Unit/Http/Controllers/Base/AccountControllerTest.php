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
use Tests\TestCase;
use Prologue\Alerts\AlertsMessageBag;
use Tests\Assertions\ControllerAssertionsTrait;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Http\Controllers\Base\AccountController;
use Pterodactyl\Http\Requests\Base\AccountDataFormRequest;

class AccountControllerTest extends TestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Http\Controllers\Base\AccountController
     */
    protected $controller;

    /**
     * @var \Pterodactyl\Http\Requests\Base\AccountDataFormRequest
     */
    protected $request;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    protected $updateService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->alert = m::mock(AlertsMessageBag::class);
        $this->request = m::mock(AccountDataFormRequest::class);
        $this->updateService = m::mock(UserUpdateService::class);

        $this->controller = new AccountController($this->alert, $this->updateService);
    }

    /**
     * Test the index controller.
     */
    public function testIndexController()
    {
        $response = $this->controller->index();

        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.account', $response);
    }

    /**
     * Test controller when password is being updated.
     */
    public function testUpdateControllerForPassword()
    {
        $this->request->shouldReceive('input')->with('do_action')->andReturn('password');
        $this->request->shouldReceive('input')->with('new_password')->once()->andReturn('test-password');

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn((object) ['id' => 1]);
        $this->updateService->shouldReceive('handle')->with(1, ['password' => 'test-password'])->once()->andReturnNull();
        $this->alert->shouldReceive('success->flash')->once()->andReturnNull();

        $response = $this->controller->update($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account', $response);
    }

    /**
     * Test controller when email is being updated.
     */
    public function testUpdateControllerForEmail()
    {
        $this->request->shouldReceive('input')->with('do_action')->andReturn('email');
        $this->request->shouldReceive('input')->with('new_email')->once()->andReturn('test@example.com');

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn((object) ['id' => 1]);
        $this->updateService->shouldReceive('handle')->with(1, ['email' => 'test@example.com'])->once()->andReturnNull();
        $this->alert->shouldReceive('success->flash')->once()->andReturnNull();

        $response = $this->controller->update($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account', $response);
    }

    /**
     * Test controller when identity is being updated.
     */
    public function testUpdateControllerForIdentity()
    {
        $this->request->shouldReceive('input')->with('do_action')->andReturn('identity');
        $this->request->shouldReceive('only')->with(['name_first', 'name_last', 'username'])->once()->andReturn([
            'test_data' => 'value',
        ]);

        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn((object) ['id' => 1]);
        $this->updateService->shouldReceive('handle')->with(1, ['test_data' => 'value'])->once()->andReturnNull();
        $this->alert->shouldReceive('success->flash')->once()->andReturnNull();

        $response = $this->controller->update($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account', $response);
    }
}
