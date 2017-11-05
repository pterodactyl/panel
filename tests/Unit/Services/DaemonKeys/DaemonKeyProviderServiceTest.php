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
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\DaemonKey;
use Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyProviderServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService|\Mockery\Mock
     */
    private $keyUpdateService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();
        Carbon::setTestNow();

        $this->keyUpdateService = m::mock(DaemonKeyUpdateService::class);
        $this->repository = m::mock(DaemonKeyRepositoryInterface::class);
    }

    /**
     * Test that a key is returned correctly as a non-admin.
     */
    public function testKeyIsReturned()
    {
        $server = factory(Server::class)->make();
        $user = factory(User::class)->make(['root_admin' => 0]);
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
     * Test that an admin user gets the server owner's key as the response.
     */
    public function testServerOwnerKeyIsReturnedIfUserIsAdministrator()
    {
        $server = factory(Server::class)->make();
        $user = factory(User::class)->make(['root_admin' => 1]);
        $key = factory(DaemonKey::class)->make();

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $server->owner_id],
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
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    private function getService(): DaemonKeyProviderService
    {
        return new DaemonKeyProviderService($this->keyUpdateService, $this->repository);
    }
}
