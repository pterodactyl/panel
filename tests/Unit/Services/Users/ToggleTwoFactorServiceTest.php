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

namespace Tests\Unit\Services\Users;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use PragmaRX\Google2FA\Contracts\Google2FA;
use Pterodactyl\Services\Users\ToggleTwoFactorService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class ToggleTwoFactorServiceTest extends TestCase
{
    /**
     * @var \PragmaRX\Google2FA\Contracts\Google2FA
     */
    protected $google2FA;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Users\ToggleTwoFactorService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->google2FA = m::mock(Google2FA::class);
        $this->repository = m::mock(UserRepositoryInterface::class);

        $this->service = new ToggleTwoFactorService($this->google2FA, $this->repository);
    }

    /**
     * Test that 2FA can be enabled for a user.
     */
    public function testTwoFactorIsEnabledForUser()
    {
        $model = factory(User::class)->make(['totp_secret' => 'secret', 'use_totp' => false]);

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
}
