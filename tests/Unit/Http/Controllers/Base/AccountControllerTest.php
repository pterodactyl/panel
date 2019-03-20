<?php

namespace Tests\Unit\Http\Controllers\Base;

use Mockery as m;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Services\Users\UserUpdateService;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Http\Controllers\Base\AccountController;
use Pterodactyl\Http\Requests\Base\AccountDataFormRequest;

class AccountControllerTest extends ControllerTestCase
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag|\Mockery\Mock
     */
    protected $alert;

    /**
     * @var \Illuminate\Auth\AuthManager|\Mockery\Mock
     */
    protected $authManager;

    /**
     * @var \Illuminate\Auth\SessionGuard|\Mockery\Mock
     */
    protected $sessionGuard;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService|\Mockery\Mock
     */
    protected $updateService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->alert = m::mock(AlertsMessageBag::class);
        $this->updateService = m::mock(UserUpdateService::class);
        $this->authManager = m::mock(AuthManager::class);
        $this->sessionGuard = m::mock(SessionGuard::class);

        $this->authManager->shouldReceive('guard')->once()->andReturn($this->sessionGuard);
    }

    /**
     * Test the index controller.
     */
    public function testIndexController()
    {
        $response = $this->getController()->index();

        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.account', $response);
    }

    /**
     * Test controller when password is being updated.
     */
    public function testUpdateControllerForPassword()
    {
        $this->setRequestMockClass(AccountDataFormRequest::class);

        $this->request->shouldReceive('input')->with('do_action')->andReturn('password');
        $this->request->shouldReceive('input')->with('new_password')->once()->andReturn('test-password');
        $this->sessionGuard->shouldReceive('logoutOtherDevices')->once()->with('test-password')->andReturnSelf();

        $this->alert->shouldReceive('success->flash')->once()->andReturnNull();

        $response = $this->getController()->update($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account', $response);
    }

    /**
     * Test controller when email is being updated.
     */
    public function testUpdateControllerForEmail()
    {
        $this->setRequestMockClass(AccountDataFormRequest::class);
        $user = $this->generateRequestUserModel();

        $this->request->shouldReceive('input')->with('do_action')->andReturn('email');
        $this->request->shouldReceive('input')->with('new_email')->once()->andReturn('test@example.com');

        $this->updateService->shouldReceive('setUserLevel')->with(User::USER_LEVEL_USER)->once()->andReturnNull();
        $this->updateService->shouldReceive('handle')->with($user, ['email' => 'test@example.com'])->once()->andReturn(collect());
        $this->alert->shouldReceive('success->flash')->once()->andReturnNull();

        $response = $this->getController()->update($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account', $response);
    }

    /**
     * Test controller when identity is being updated.
     */
    public function testUpdateControllerForIdentity()
    {
        $this->setRequestMockClass(AccountDataFormRequest::class);
        $user = $this->generateRequestUserModel();

        $this->request->shouldReceive('input')->with('do_action')->andReturn('identity');
        $this->request->shouldReceive('only')->with(['name_first', 'name_last', 'username', 'language', 'oauth2_id'])->once()->andReturn([
            'test_data' => 'value',
        ]);

        $this->updateService->shouldReceive('setUserLevel')->with(User::USER_LEVEL_USER)->once()->andReturnNull();
        $this->updateService->shouldReceive('handle')->with($user, ['test_data' => 'value'])->once()->andReturn(collect());
        $this->alert->shouldReceive('success->flash')->once()->andReturnNull();

        $response = $this->getController()->update($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account', $response);
    }

    /**
     * Return an instance of the controller for testing.
     *
     * @return \Pterodactyl\Http\Controllers\Base\AccountController
     */
    private function getController(): AccountController
    {
        return new AccountController($this->alert, $this->authManager, $this->updateService);
    }
}
