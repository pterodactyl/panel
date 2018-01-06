<?php

namespace Tests\Unit\Http\Controllers\Base;

use Mockery as m;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Config\Repository;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Services\Users\TwoFactorSetupService;
use Pterodactyl\Services\Users\ToggleTwoFactorService;
use Pterodactyl\Http\Controllers\Base\SecurityController;
use Pterodactyl\Contracts\Repository\SessionRepositoryInterface;
use Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid;

class SecurityControllerTest extends ControllerTestCase
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag|\Mockery\Mock
     */
    protected $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\SessionRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Users\ToggleTwoFactorService|\Mockery\Mock
     */
    protected $toggleTwoFactorService;

    /**
     * @var \Pterodactyl\Services\Users\TwoFactorSetupService|\Mockery\Mock
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
        $this->toggleTwoFactorService = m::mock(ToggleTwoFactorService::class);
        $this->twoFactorSetupService = m::mock(TwoFactorSetupService::class);
    }

    /**
     * Test the index controller when using a database driver.
     */
    public function testIndexControllerWithDatabaseDriver()
    {
        $model = $this->generateRequestUserModel();

        $this->config->shouldReceive('get')->with('session.driver')->once()->andReturn('database');
        $this->repository->shouldReceive('getUserSessions')->with($model->id)->once()->andReturn(collect(['sessions']));

        $response = $this->getController()->index($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.security', $response);
        $this->assertViewHasKey('sessions', $response);
        $this->assertViewKeyEquals('sessions', collect(['sessions']), $response);
    }

    /**
     * Test the index controller when not using the database driver.
     */
    public function testIndexControllerWithoutDatabaseDriver()
    {
        $this->config->shouldReceive('get')->with('session.driver')->once()->andReturn('redis');

        $response = $this->getController()->index($this->request);
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
        $model = $this->generateRequestUserModel();

        $this->twoFactorSetupService->shouldReceive('handle')->with($model)->once()->andReturn('qrCodeImage');

        $response = $this->getController()->generateTotp($this->request);
        $this->assertIsJsonResponse($response);
        $this->assertResponseJsonEquals(['qrImage' => 'qrCodeImage'], $response);
    }

    /**
     * Test the disable totp controller when no exception is thrown by the service.
     */
    public function testDisableTotpControllerSuccess()
    {
        $model = $this->generateRequestUserModel();

        $this->request->shouldReceive('input')->with('token')->once()->andReturn('testToken');
        $this->toggleTwoFactorService->shouldReceive('handle')->with($model, 'testToken', false)->once()->andReturn(true);

        $response = $this->getController()->disableTotp($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account.security', $response);
    }

    /**
     * Test the disable totp controller when an exception is thrown by the service.
     */
    public function testDisableTotpControllerWhenExceptionIsThrown()
    {
        $model = $this->generateRequestUserModel();

        $this->request->shouldReceive('input')->with('token')->once()->andReturn('testToken');
        $this->toggleTwoFactorService->shouldReceive('handle')->with($model, 'testToken', false)->once()->andThrow(new TwoFactorAuthenticationTokenInvalid);
        $this->alert->shouldReceive('danger')->with(trans('base.security.2fa_disable_error'))->once()->andReturnSelf();
        $this->alert->shouldReceive('flash')->withNoArgs()->once()->andReturnNull();

        $response = $this->getController()->disableTotp($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account.security', $response);
    }

    /**
     * Test the revoke controller.
     */
    public function testRevokeController()
    {
        $model = $this->generateRequestUserModel();

        $this->repository->shouldReceive('deleteUserSession')->with($model->id, 123)->once()->andReturnNull();

        $response = $this->getController()->revoke($this->request, 123);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account.security', $response);
    }

    /**
     * Return an instance of the controller for testing with mocked dependencies.
     *
     * @return \Pterodactyl\Http\Controllers\Base\SecurityController
     */
    private function getController(): SecurityController
    {
        return new SecurityController(
            $this->alert,
            $this->config,
            $this->repository,
            $this->toggleTwoFactorService,
            $this->twoFactorSetupService
        );
    }
}
