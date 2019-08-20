<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\DaemonKeys;

use Mockery as m;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Server;
use App\Models\Subuser;
use App\Models\DaemonKey;
use App\Services\DaemonKeys\DaemonKeyUpdateService;
use App\Services\DaemonKeys\DaemonKeyCreationService;
use App\Services\DaemonKeys\DaemonKeyProviderService;
use App\Exceptions\Repository\RecordNotFoundException;
use App\Contracts\Repository\SubuserRepositoryInterface;
use App\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyProviderServiceTest extends TestCase
{
    /**
     * @var \App\Services\DaemonKeys\DaemonKeyCreationService|\Mockery\Mock
     */
    private $keyCreationService;

    /**
     * @var \App\Services\DaemonKeys\DaemonKeyUpdateService|\Mockery\Mock
     */
    private $keyUpdateService;

    /**
     * @var \App\Contracts\Repository\DaemonKeyRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \App\Contracts\Repository\SubuserRepositoryInterface|\Mockery\Mock
     */
    private $subuserRepository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        $this->keyCreationService = m::mock(DaemonKeyCreationService::class);
        $this->keyUpdateService = m::mock(DaemonKeyUpdateService::class);
        $this->repository = m::mock(DaemonKeyRepositoryInterface::class);
        $this->subuserRepository = m::mock(SubuserRepositoryInterface::class);
    }

    /**
     * Test that a key is returned correctly as a non-admin.
     */
    public function testKeyIsReturned()
    {
        $server = factory(Server::class)->make();
        $user = factory(User::class)->make();
        $key = factory(DaemonKey::class)->make();

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andReturn($key);

        $response = $this->getService()->handle($server, $user);
        $this->assertNotEmpty($response);
        $this->assertEquals($key->secret, $response);
    }

    /**
     * Test that an expired key is updated and then returned.
     */
    public function testExpiredKeyIsUpdated()
    {
        $server = factory(Server::class)->make();
        $user = factory(User::class)->make(['root_admin' => 0]);
        $key = factory(DaemonKey::class)->make(['expires_at' => Carbon::now()->subHour()]);

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andReturn($key);

        $this->keyUpdateService->shouldReceive('handle')->with($key->id)->once()->andReturn('abc123');

        $response = $this->getService()->handle($server, $user);
        $this->assertNotEmpty($response);
        $this->assertEquals('abc123', $response);
    }

    /**
     * Test that an expired key is not updated and the expired key is returned.
     */
    public function testExpiredKeyIsNotUpdated()
    {
        $server = factory(Server::class)->make();
        $user = factory(User::class)->make(['root_admin' => 0]);
        $key = factory(DaemonKey::class)->make(['expires_at' => Carbon::now()->subHour()]);

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andReturn($key);

        $response = $this->getService()->handle($server, $user, false);
        $this->assertNotEmpty($response);
        $this->assertEquals($key->secret, $response);
    }

    /**
     * Test that a key is created if it is missing and the user is a
     * root administrator.
     */
    public function testMissingKeyIsCreatedIfRootAdmin()
    {
        $server = factory(Server::class)->make();
        $user = factory(User::class)->make(['root_admin' => 1]);
        $key = factory(DaemonKey::class)->make(['expires_at' => Carbon::now()->subHour()]);

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andThrow(new RecordNotFoundException);

        $this->keyCreationService->shouldReceive('handle')->with($server->id, $user->id)->once()->andReturn($key->secret);

        $response = $this->getService()->handle($server, $user, false);
        $this->assertNotEmpty($response);
        $this->assertEquals($key->secret, $response);
    }

    /**
     * Test that a key is created if it is missing and the user is the
     * server owner.
     */
    public function testMissingKeyIsCreatedIfUserIsServerOwner()
    {
        $user = factory(User::class)->make(['root_admin' => 0]);
        $server = factory(Server::class)->make(['owner_id' => $user->id]);
        $key = factory(DaemonKey::class)->make(['expires_at' => Carbon::now()->subHour()]);

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andThrow(new RecordNotFoundException);

        $this->keyCreationService->shouldReceive('handle')->with($server->id, $user->id)->once()->andReturn($key->secret);

        $response = $this->getService()->handle($server, $user, false);
        $this->assertNotEmpty($response);
        $this->assertEquals($key->secret, $response);
    }

    /**
     * Test that a missing key is created for a subuser.
     */
    public function testMissingKeyIsCreatedForSubuser()
    {
        $user = factory(User::class)->make(['root_admin' => 0]);
        $server = factory(Server::class)->make();
        $key = factory(DaemonKey::class)->make(['expires_at' => Carbon::now()->subHour()]);
        $subuser = factory(Subuser::class)->make(['user_id' => $user->id, 'server_id' => $server->id]);

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andThrow(new RecordNotFoundException);

        $this->subuserRepository->shouldReceive('findFirstWhere')->once()->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->andReturn($subuser);

        $this->keyCreationService->shouldReceive('handle')->with($server->id, $user->id)->once()->andReturn($key->secret);

        $response = $this->getService()->handle($server, $user, false);
        $this->assertNotEmpty($response);
        $this->assertEquals($key->secret, $response);
    }

    /**
     * Test that an exception is thrown if the user should not get a key.
     *
     * @expectedException \App\Exceptions\Repository\RecordNotFoundException
     */
    public function testExceptionIsThrownIfUserDoesNotDeserveKey()
    {
        $server = factory(Server::class)->make();
        $user = factory(User::class)->make(['root_admin' => 0]);

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andThrow(new RecordNotFoundException);

        $this->subuserRepository->shouldReceive('findFirstWhere')->once()->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->andThrow(new RecordNotFoundException);

        $this->getService()->handle($server, $user, false);
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \App\Services\DaemonKeys\DaemonKeyProviderService
     */
    private function getService(): DaemonKeyProviderService
    {
        return new DaemonKeyProviderService(
            $this->keyCreationService,
            $this->repository,
            $this->keyUpdateService,
            $this->subuserRepository
        );
    }
}
