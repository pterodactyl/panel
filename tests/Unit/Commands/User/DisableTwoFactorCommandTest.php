<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Commands\User;

use Mockery as m;
use Pterodactyl\Models\User;
use Tests\Unit\Commands\CommandTestCase;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Console\Commands\User\DisableTwoFactorCommand;

class DisableTwoFactorCommandTest extends CommandTestCase
{
    /**
     * @var \Pterodactyl\Console\Commands\User\DisableTwoFactorCommand
     */
    protected $command;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * Setup tests.
     */
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

        $this->repository->shouldReceive('setColumns')->with(['id', 'email'])->once()->andReturnSelf()
            ->shouldReceive('findFirstWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($user->id, [
                'use_totp' => false,
                'totp_secret' => null,
            ])->once()->andReturnNull();

        $display = $this->runCommand($this->command, [], [$user->email]);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.2fa_disabled', ['email' => $user->email]), $display);
    }

    /**
     * Test 2-factor auth is disabled when user is passed in option.
     */
    public function testTwoFactorIsDisabledWhenOptionIsPassed()
    {
        $user = factory(User::class)->make();

        $this->repository->shouldReceive('setColumns')->with(['id', 'email'])->once()->andReturnSelf()
            ->shouldReceive('findFirstWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($user->id, [
                'use_totp' => false,
                'totp_secret' => null,
            ])->once()->andReturnNull();

        $display = $this->withoutInteraction()->runCommand($this->command, ['--email' => $user->email]);
        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.2fa_disabled', ['email' => $user->email]), $display);
    }
}
