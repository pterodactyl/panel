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
use Illuminate\Contracts\Hashing\Hasher;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserUpdateServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->hasher = m::mock(Hasher::class);
        $this->repository = m::mock(UserRepositoryInterface::class);

        $this->service = new UserUpdateService($this->hasher, $this->repository);
    }

    /**
     * Test that the handle function does not attempt to hash a password if no password is passed.
     */
    public function testUpdateUserWithoutTouchingHasherIfNoPasswordPassed()
    {
        $this->repository->shouldReceive('update')->with(1, ['test-data' => 'value'])->once()->andReturnNull();

        $this->assertNull($this->service->handle(1, ['test-data' => 'value']));
    }

    /**
     * Test that the handle function hashes a password if passed in the data array.
     */
    public function testUpdateUserAndHashPasswordIfProvided()
    {
        $this->hasher->shouldReceive('make')->with('raw_pass')->once()->andReturn('enc_pass');
        $this->repository->shouldReceive('update')->with(1, ['password' => 'enc_pass'])->once()->andReturnNull();

        $this->assertNull($this->service->handle(1, ['password' => 'raw_pass']));
    }
}
