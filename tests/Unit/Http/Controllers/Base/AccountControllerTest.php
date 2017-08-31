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
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Base\AccountController;
use Pterodactyl\Http\Requests\Base\AccountDataFormRequest;
use Pterodactyl\Services\Users\UserUpdateService;
use Tests\Assertions\ControllerAssertionsTrait;
use Tests\TestCase;

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
        $this->assertRouteRedirectEquals('account', $response);
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
        $this->assertRouteRedirectEquals('account', $response);
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
        $this->assertRouteRedirectEquals('account', $response);
    }
}
