<?php

namespace Tests\Unit\Services\Users;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Services\Users\TwoFactorSetupService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class TwoFactorSetupServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \PragmaRX\Google2FA\Google2FA
     */
    protected $google2FA;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Users\TwoFactorSetupService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->google2FA = m::mock(Google2FA::class);
        $this->repository = m::mock(UserRepositoryInterface::class);

        $this->service = new TwoFactorSetupService($this->config, $this->google2FA, $this->repository);
    }

    /**
     * Test that the correct data is returned.
     */
    public function testSecretAndImageAreReturned()
    {
        $model = factory(User::class)->make();

        $this->config->shouldReceive('get')->with('pterodactyl.auth.2fa.bytes')->once()->andReturn(32);
        $this->google2FA->shouldReceive('generateSecretKey')->with(32)->once()->andReturn('secretKey');
        $this->config->shouldReceive('get')->with('app.name')->once()->andReturn('CompanyName');
        $this->google2FA->shouldReceive('getQRCodeGoogleUrl')->with('CompanyName', $model->email, 'secretKey')
            ->once()->andReturn('http://url.com');
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($model->id, ['totp_secret' => 'secretKey'])->once()->andReturnNull();

        $response = $this->service->handle($model);
        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('qrImage', $response);
        $this->assertArrayHasKey('secret', $response);
        $this->assertEquals('http://url.com', $response['qrImage']);
        $this->assertEquals('secretKey', $response['secret']);
    }

    /**
     * Test that an integer can be passed in place of the user model.
     */
    public function testIntegerCanBePassedInPlaceOfUserModel()
    {
        $model = factory(User::class)->make();

        $this->repository->shouldReceive('find')->with($model->id)->once()->andReturn($model);
        $this->google2FA->shouldReceive('generateSecretKey')->withNoArgs()->once()->andReturnNull();
        $this->config->shouldReceive('get')->with('app.name')->once()->andReturnNull();
        $this->google2FA->shouldReceive('getQRCodeGoogleUrl')->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFresh->update')->once()->andReturnNull();

        $this->assertTrue(is_array($this->service->handle($model->id)));
    }
}
