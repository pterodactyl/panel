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
use Pterodactyl\Models\User;
use Tests\Unit\Commands\CommandTestCase;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Console\Commands\User\MakeUserCommand;

class MakeUserCommandTest extends CommandTestCase
{
    /**
     * @var \Pterodactyl\Console\Commands\User\MakeUserCommand
     */
    protected $command;

    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    protected $creationService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->creationService = m::mock(UserCreationService::class);

        $this->command = new MakeUserCommand($this->creationService);
        $this->command->setLaravel($this->app);
    }

    /**
     * Test that the command executes if no options are passed.
     */
    public function testCommandWithNoPassedOptions()
    {
        $user = factory(User::class)->make(['root_admin' => true]);

        $this->creationService->shouldReceive('handle')->with([
            'email' => $user->email,
            'username' => $user->username,
            'name_first' => $user->name_first,
            'name_last' => $user->name_last,
            'password' => 'Password123',
            'root_admin' => $user->root_admin,
        ])->once()->andReturn($user);

        $display = $this->runCommand($this->command, [], [
            'yes', $user->email, $user->username, $user->name_first, $user->name_last, 'Password123',
        ]);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.ask_password_help'), $display);
        $this->assertContains($user->uuid, $display);
        $this->assertContains($user->email, $display);
        $this->assertContains($user->username, $display);
        $this->assertContains($user->name, $display);
        $this->assertContains('Yes', $display);
    }

    /**
     * Test that the --no-password flag works as intended.
     */
    public function testCommandWithNoPasswordOption()
    {
        $user = factory(User::class)->make(['root_admin' => true]);

        $this->creationService->shouldReceive('handle')->with([
            'email' => $user->email,
            'username' => $user->username,
            'name_first' => $user->name_first,
            'name_last' => $user->name_last,
            'password' => null,
            'root_admin' => $user->root_admin,
        ])->once()->andReturn($user);

        $display = $this->runCommand($this->command, ['--no-password' => true], [
            'yes', $user->email, $user->username, $user->name_first, $user->name_last,
        ]);

        $this->assertNotEmpty($display);
        $this->assertNotContains(trans('command/messages.user.ask_password_help'), $display);
    }

    /**
     * Test command when arguments are passed as flags.
     */
    public function testCommandWithOptionsPassed()
    {
        $user = factory(User::class)->make(['root_admin' => false]);

        $this->creationService->shouldReceive('handle')->with([
            'email' => $user->email,
            'username' => $user->username,
            'name_first' => $user->name_first,
            'name_last' => $user->name_last,
            'password' => 'Password123',
            'root_admin' => $user->root_admin,
        ])->once()->andReturn($user);

        $display = $this->withoutInteraction()->runCommand($this->command, [
            '--email' => $user->email,
            '--username' => $user->username,
            '--name-first' => $user->name_first,
            '--name-last' => $user->name_last,
            '--password' => 'Password123',
            '--admin' => 0,
        ]);

        $this->assertNotEmpty($display);
        $this->assertNotContains(trans('command/messages.user.ask_password_help'), $display);
        $this->assertContains($user->uuid, $display);
        $this->assertContains($user->email, $display);
        $this->assertContains($user->username, $display);
        $this->assertContains($user->name, $display);
        $this->assertContains('No', $display);
    }
}
