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

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Connection;
use Mockery as m;
use Pterodactyl\Models\User;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Services\Helpers\TemporaryPasswordService;
use Pterodactyl\Services\UserService;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    protected $database;

    protected $hasher;

    protected $model;

    protected $passwordService;

    protected $service;

    public function setUp()
    {
        parent::setUp();

        $this->database = m::mock(Connection::class);
        $this->hasher = m::mock(Hasher::class);
        $this->passwordService = m::mock(TemporaryPasswordService::class);
        $this->model = m::mock(User::class);
        $this->app->instance(AccountCreated::class, m::mock(AccountCreated::class));

        $this->service = new UserService(
            $this->database,
            $this->hasher,
            $this->passwordService,
            $this->model
        );
    }

    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testCreateFunction()
    {
        $data = ['password' => 'password'];

        $this->hasher->shouldReceive('make')->once()->with($data['password'])->andReturn('hashString');
        $this->database->shouldReceive('transaction')->andReturnNull();

        $this->model->shouldReceive('newInstance')->with(['password' => 'hashString'])->andReturnSelf();
        $this->model->shouldReceive('save')->andReturn(true);
        $this->model->shouldReceive('notify')->with(m::type(AccountCreated::class))->andReturnNull();
        $this->model->shouldReceive('getAttribute')->andReturnSelf();

        $response = $this->service->create($data);

        $this->assertNotNull($response);
        $this->assertInstanceOf(User::class, $response);
    }

    public function testCreateFunctionWithoutPassword()
    {
        $data = ['email' => 'user@example.com'];

        $this->hasher->shouldNotReceive('make');
        $this->model->shouldReceive('newInstance')->with($data)->andReturnSelf();

        $this->database->shouldReceive('transaction')->andReturn('authToken');
        $this->hasher->shouldReceive('make')->andReturn('randomString');
        $this->passwordService->shouldReceive('generateReset')->with($data['email'])->andReturn('authToken');
        $this->model->shouldReceive('save')->withNoArgs()->andReturn(true);

        $this->model->shouldReceive('notify')->with(m::type(AccountCreated::class))->andReturnNull();
        $this->model->shouldReceive('getAttribute')->andReturnSelf();

        $response = $this->service->create($data);

        $this->assertNotNull($response);
        $this->assertInstanceOf(User::class, $response);
    }
}
