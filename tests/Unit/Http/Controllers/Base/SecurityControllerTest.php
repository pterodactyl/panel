<?php

namespace Tests\Unit\Http\Controllers\Base;

use Mockery as m;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
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
     * Test TOTP generation controller.
     */
    public function testIndexWithout2FactorEnabled()
    {
        $model = $this->generateRequestUserModel(['use_totp' => 0]);

        $this->twoFactorSetupService->shouldReceive('handle')->with($model)->once()->andReturn(new Collection([
            'image' => 'test-image',
            'secret' => 'secret-code',
        ]));

        $response = $this->getController()->index($this->request);
        $this->assertIsJsonResponse($response);
        $this->assertResponseCodeEquals(Response::HTTP_OK, $response);
        $this->assertResponseJsonEquals(['enabled' => false, 'qr_image' => 'test-image', 'secret' => 'secret-code'], $response);
        $this->assertResponseJsonEquals(['qrImage' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=qrCodeImage'], $response);
    }

    /**
     * Test TOTP setting controller when no exception is thrown by the service.
     */
    public function testIndexWith2FactorEnabled()
    {
        $this->generateRequestUserModel(['use_totp' => 1]);

        $response = $this->getController()->index($this->request);
        $this->assertIsJsonResponse($response);
        $this->assertResponseCodeEquals(Response::HTTP_OK, $response);
        $this->assertResponseJsonEquals(['enabled' => true], $response);
    }

    /**
     * Test that a 2FA token can be stored or deleted.
     *
     * @param string $func
     * @dataProvider functionCallDataProvider
     */
    public function testStore(string $func)
    {
        $model = $this->generateRequestUserModel();

        $this->mockRequestInput('token', 'some-token');

        if ($func === 'delete') {
            $this->toggleTwoFactorService->shouldReceive('handle')->with($model, 'some-token', false);
        } else {
            $this->toggleTwoFactorService->shouldReceive('handle')->with($model, 'some-token');
        }

        $response = $this->getController()->{$func}($this->request);
        $this->assertIsJsonResponse($response);
        $this->assertResponseCodeEquals(Response::HTTP_OK, $response);
        $this->assertResponseJsonEquals(['success' => true], $response);
    }

    /**
     * Test an invalid token exception is handled.
     *
     * @param string $func
     * @dataProvider functionCallDataProvider
     */
    public function testStoreWithInvalidTokenException(string $func)
    {
        $this->generateRequestUserModel();

        $this->mockRequestInput('token');
        $this->toggleTwoFactorService->shouldReceive('handle')->andThrow(new TwoFactorAuthenticationTokenInvalid);

        $response = $this->getController()->{$func}($this->request);
        $this->assertIsJsonResponse($response);
        $this->assertResponseCodeEquals(Response::HTTP_OK, $response);
        $this->assertResponseJsonEquals(['success' => false], $response);
    }

    /**
     * @return array
     */
    public function functionCallDataProvider()
    {
        return [['store'], ['delete']];
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
