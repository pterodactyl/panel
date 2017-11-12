<?php

namespace Tests\Unit\Services\Users;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Users\ToggleTwoFactorService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class ToggleTwoFactorServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    private $config;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter|\Mockery\Mock
     */
    private $encrypter;

    /**
     * @var \PragmaRX\Google2FA\Google2FA|\Mockery\Mock
     */
    private $google2FA;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->google2FA = m::mock(Google2FA::class);
        $this->repository = m::mock(UserRepositoryInterface::class);
    }

    /**
     * Test that 2FA can be enabled for a user.
     */
    public function testTwoFactorIsEnabledForUser()
    {
        $model = factory(User::class)->make(['totp_secret' => 'secret', 'use_totp' => false]);

        $this->config->shouldReceive('get')->with('pterodactyl.auth.2fa.window')->once()->andReturn(4);
        $this->google2FA->shouldReceive('verifyKey')->with($model->totp_secret, 'test-token', 2)->once()->andReturn(true);
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($model->id, ['use_totp' => true])->once()->andReturnNull();

        $this->assertTrue($this->service->handle($model, 'test-token'));
    }

    /**
     * Test that 2FA can be disabled for a user.
     */
    public function testTwoFactorIsDisabled()
    {
        $model = factory(User::class)->make(['totp_secret' => 'secret', 'use_totp' => true]);

        $this->google2FA->shouldReceive('verifyKey')->with($model->totp_secret, 'test-token', 2)->once()->andReturn(true);
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($model->id, ['use_totp' => false])->once()->andReturnNull();

        $this->assertTrue($this->service->handle($model, 'test-token'));
    }

    /**
     * Test that 2FA will remain disabled for a user.
     */
    public function testTwoFactorRemainsDisabledForUser()
    {
        $model = factory(User::class)->make(['totp_secret' => 'secret', 'use_totp' => false]);

        $this->google2FA->shouldReceive('verifyKey')->with($model->totp_secret, 'test-token', 2)->once()->andReturn(true);
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($model->id, ['use_totp' => false])->once()->andReturnNull();

        $this->assertTrue($this->service->handle($model, 'test-token', false));
    }

    /**
     * Test that an exception is thrown if the token provided is invalid.
     *
     * @expectedException \Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid
     */
    public function testExceptionIsThrownIfTokenIsInvalid()
    {
        $model = factory(User::class)->make();
        $this->google2FA->shouldReceive('verifyKey')->once()->andReturn(false);

        $this->service->handle($model, 'test-token');
    }

    /**
     * Test that an integer can be passed in place of a user model.
     */
    public function testIntegerCanBePassedInPlaceOfUserModel()
    {
        $model = factory(User::class)->make(['totp_secret' => 'secret', 'use_totp' => false]);

        $this->repository->shouldReceive('find')->with($model->id)->once()->andReturn($model);
        $this->google2FA->shouldReceive('verifyKey')->once()->andReturn(true);
        $this->repository->shouldReceive('withoutFresh->update')->once()->andReturnNull();

        $this->assertTrue($this->service->handle($model->id, 'test-token'));
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Users\ToggleTwoFactorService
     */
    private function getService(): ToggleTwoFactorService
    {
        return new ToggleTwoFactorService($this->encrypter, $this->google2FA, $this->config, $this->repository);
    }
}
