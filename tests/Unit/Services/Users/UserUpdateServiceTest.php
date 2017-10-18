<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
