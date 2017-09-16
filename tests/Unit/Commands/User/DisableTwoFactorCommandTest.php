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

namespace Tests\Unit\Commands\User;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Symfony\Component\Console\Tester\CommandTester;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Console\Commands\User\DisableTwoFactorCommand;

class DisableTwoFactorCommandTest extends TestCase
{
    /**
     * @var \Pterodactyl\Console\Commands\User\DisableTwoFactorCommand
     */
    protected $command;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(UserRepositoryInterface::class);

        $this->command = new DisableTwoFactorCommand($this->repository);
        $this->command->setLaravel($this->app);
    }

    /**
     * Test 2-factor auth is disabled when no option is passed.
     */
    public function testTwoFactorIsDisabledWhenNoOptionIsPassed()
    {
        $user = factory(User::class)->make();

        $this->repository->shouldReceive('withColumns')->with(['id', 'email'])->once()->andReturnSelf()
            ->shouldReceive('findFirstWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($user->id, [
                'use_totp' => false,
                'totp_secret' => null,
            ])->once()->andReturnNull();

        $response = new CommandTester($this->command);
        $response->setInputs([$user->email]);
        $response->execute([]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.2fa_disabled', ['email' => $user->email]), $display);
    }

    /**
     * Test 2-factor auth is disabled when user is passed in option.
     */
    public function testTwoFactorIsDisabledWhenOptionIsPassed()
    {
        $user = factory(User::class)->make();

        $this->repository->shouldReceive('withColumns')->with(['id', 'email'])->once()->andReturnSelf()
            ->shouldReceive('findFirstWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($user->id, [
                'use_totp' => false,
                'totp_secret' => null,
            ])->once()->andReturnNull();

        $response = new CommandTester($this->command);
        $response->execute([
            '--email' => $user->email,
        ]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.2fa_disabled', ['email' => $user->email]), $display);
    }
}
