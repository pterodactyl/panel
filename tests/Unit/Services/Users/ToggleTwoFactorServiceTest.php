<?php

namespace Tests\Unit\Services\Users;

use Mockery as m;
use Carbon\Carbon;
use Tests\TestCase;
use Pterodactyl\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Users\ToggleTwoFactorService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class ToggleTwoFactorServiceTest extends TestCase
{
    const TEST_WINDOW_INT = 4;
    const USER_TOTP_SECRET = 'encryptedValue';
    const DECRYPTED_USER_SECRET = 'decryptedValue';

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
        Carbon::setTestNow(Carbon::now());

        $this->config = m::mock(Repository::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->google2FA = m::mock(Google2FA::class);
        $this->repository = m::mock(UserRepositoryInterface::class);

        $this->config->shouldReceive('get')->with('pterodactyl.auth.2fa.window')->once()->andReturn(self::TEST_WINDOW_INT);
        $this->encrypter->shouldReceive('decrypt')->with(self::USER_TOTP_SECRET)->once()->andReturn(self::DECRYPTED_USER_SECRET);
    }

    /**
     * Test that 2FA can be enabled for a user.
     */
    public function testTwoFactorIsEnabledForUser()
    {
        $model = factory(User::class)->make(['totp_secret' => self::USER_TOTP_SECRET, 'use_totp' => false]);

        $this->google2FA->shouldReceive('verifyKey')->with(self::DECRYPTED_USER_SECRET, 'test-token', self::TEST_WINDOW_INT)->once()->andReturn(true);
        $this->repository->shouldReceive('withoutFreshModel->update')->with($model->id, [
            'totp_authenticated_at' => Carbon::now(),
            'use_totp' => true,
        ])->once()->andReturnNull();

        $this->assertTrue($this->getService()->handle($model, 'test-token'));
    }

    /**
     * Test that 2FA can be disabled for a user.
     */
    public function testTwoFactorIsDisabled()
    {
        $model = factory(User::class)->make(['totp_secret' => self::USER_TOTP_SECRET, 'use_totp' => true]);

        $this->google2FA->shouldReceive('verifyKey')->with(self::DECRYPTED_USER_SECRET, 'test-token', self::TEST_WINDOW_INT)->once()->andReturn(true);
        $this->repository->shouldReceive('withoutFreshModel->update')->with($model->id, [
            'totp_authenticated_at' => Carbon::now(),
            'use_totp' => false,
        ])->once()->andReturnNull();

        $this->assertTrue($this->getService()->handle($model, 'test-token'));
    }

    /**
     * Test that 2FA will remain disabled for a user.
     */
    public function testTwoFactorRemainsDisabledForUser()
    {
        $model = factory(User::class)->make(['totp_secret' => self::USER_TOTP_SECRET, 'use_totp' => false]);

        $this->google2FA->shouldReceive('verifyKey')->with(self::DECRYPTED_USER_SECRET, 'test-token', self::TEST_WINDOW_INT)->once()->andReturn(true);
        $this->repository->shouldReceive('withoutFreshModel->update')->with($model->id, [
            'totp_authenticated_at' => Carbon::now(),
            'use_totp' => false,
        ])->once()->andReturnNull();

        $this->assertTrue($this->getService()->handle($model, 'test-token', false));
    }

    /**
     * Test that an exception is thrown if the token provided is invalid.
     *
     * @expectedException \Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid
     */
    public function testExceptionIsThrownIfTokenIsInvalid()
    {
        $model = factory(User::class)->make(['totp_secret' => self::USER_TOTP_SECRET]);
        $this->google2FA->shouldReceive('verifyKey')->once()->andReturn(false);

        $this->getService()->handle($model, 'test-token');
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
