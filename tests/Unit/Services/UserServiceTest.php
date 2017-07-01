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

namespace Tests\Unit\Services;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Services\UserService;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Notifications\ChannelManager;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Services\Helpers\TemporaryPasswordService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $appMock;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $database;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * @var \Illuminate\Notifications\ChannelManager
     */
    protected $notification;

    /**
     * @var \Pterodactyl\Services\Helpers\TemporaryPasswordService
     */
    protected $passwordService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\UserService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->appMock = m::mock(Application::class);
        $this->database = m::mock(ConnectionInterface::class);
        $this->hasher = m::mock(Hasher::class);
        $this->notification = m::mock(ChannelManager::class);
        $this->passwordService = m::mock(TemporaryPasswordService::class);
        $this->repository = m::mock(UserRepositoryInterface::class);

        $this->service = new UserService(
            $this->appMock,
            $this->notification,
            $this->database,
            $this->hasher,
            $this->passwordService,
            $this->repository
        );
    }

    /**
     * Test that a user is created when a password is passed.
     */
    public function test_user_creation_with_password()
    {
        $user = (object) [
            'name_first' => 'FirstName',
            'username' => 'user_name',
        ];

        $this->hasher->shouldReceive('make')->with('raw-password')->once()->andReturn('enc-password');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->hasher->shouldNotReceive('make');
        $this->passwordService->shouldNotReceive('generateReset');
        $this->repository->shouldReceive('create')->with(['password' => 'enc-password'])->once()->andReturn($user);
        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();
        $this->appMock->shouldReceive('makeWith')->with(AccountCreated::class, [
            'user' => [
                'name' => 'FirstName',
                'username' => 'user_name',
                'token' => null,
            ],
        ])->once()->andReturnNull();

        $this->notification->shouldReceive('send')->with($user, null)->once()->andReturnNull();

        $response = $this->service->create([
            'password' => 'raw-password',
        ]);

        $this->assertNotNull($response);
        $this->assertEquals($user->username, $response->username);
        $this->assertEquals($user->name_first, 'FirstName');
    }

    /**
     * Test that a user is created with a random password when no password is provided.
     */
    public function test_user_creation_without_password()
    {
        $user = (object) [
            'name_first' => 'FirstName',
            'username' => 'user_name',
            'email' => 'user@example.com',
        ];

        $this->hasher->shouldNotReceive('make');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->hasher->shouldReceive('make')->once()->andReturn('created-enc-password');
        $this->passwordService->shouldReceive('generateReset')->with('user@example.com')->once()->andReturn('random-token');

        $this->repository->shouldReceive('create')->with([
            'password' => 'created-enc-password',
            'email' => 'user@example.com',
        ])->once()->andReturn($user);

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();
        $this->appMock->shouldReceive('makeWith')->with(AccountCreated::class, [
            'user' => [
                'name' => 'FirstName',
                'username' => 'user_name',
                'token' => 'random-token',
            ],
        ])->once()->andReturnNull();

        $this->notification->shouldReceive('send')->with($user, null)->once()->andReturnNull();

        $response = $this->service->create([
            'email' => 'user@example.com',
        ]);

        $this->assertNotNull($response);
        $this->assertEquals($user->username, $response->username);
        $this->assertEquals($user->name_first, 'FirstName');
        $this->assertEquals($user->email, $response->email);
    }

    /**
     * Test that passing no password will not attempt any hashing.
     */
    public function test_user_update_without_password()
    {
        $this->hasher->shouldNotReceive('make');
        $this->repository->shouldReceive('update')->with(1, ['email' => 'new@example.com'])->once()->andReturnNull();

        $response = $this->service->update(1, ['email' => 'new@example.com']);

        $this->assertNull($response);
    }

    /**
     * Test that passing a password will hash it before storage.
     */
    public function test_user_update_with_password()
    {
        $this->hasher->shouldReceive('make')->with('password')->once()->andReturn('enc-password');
        $this->repository->shouldReceive('update')->with(1, ['password' => 'enc-password'])->once()->andReturnNull();

        $response = $this->service->update(1, ['password' => 'password']);

        $this->assertNull($response);
    }

}
