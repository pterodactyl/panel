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
use Pterodactyl\Models\DaemonKey;
use Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyProviderServiceTest extends TestCase
{
    /**
     * @var \Carbon\Carbon|\Mockery\Mock
     */
    protected $carbon;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService|\Mockery\Mock
     */
    protected $keyUpdateService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->carbon = new Carbon();
        $this->carbon->setTestNow();

        $this->keyUpdateService = m::mock(DaemonKeyUpdateService::class);
        $this->repository = m::mock(DaemonKeyRepositoryInterface::class);

        $this->service = new DaemonKeyProviderService($this->carbon, $this->keyUpdateService, $this->repository);
    }

    /**
     * Test that a key is returned.
     */
    public function testKeyIsReturned()
    {
        $key = factory(DaemonKey::class)->make();

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $key->user_id],
            ['server_id', '=', $key->server_id],
        ])->once()->andReturn($key);

        $response = $this->service->handle($key->server_id, $key->user_id);
        $this->assertNotEmpty($response);
        $this->assertEquals($key->secret, $response);
    }

    /**
     * Test that an expired key is updated and then returned.
     */
    public function testExpiredKeyIsUpdated()
    {
        $key = factory(DaemonKey::class)->make([
            'expires_at' => $this->carbon->subHour(),
        ]);

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $key->user_id],
            ['server_id', '=', $key->server_id],
        ])->once()->andReturn($key);

        $this->keyUpdateService->shouldReceive('handle')->with($key->id)->once()->andReturn(true);

        $response = $this->service->handle($key->server_id, $key->user_id);
        $this->assertNotEmpty($response);
        $this->assertTrue($response);
    }

    /**
     * Test that an expired key is not updated and the expired key is returned.
     */
    public function testExpiredKeyIsNotUpdated()
    {
        $key = factory(DaemonKey::class)->make([
            'expires_at' => $this->carbon->subHour(),
        ]);

        $this->repository->shouldReceive('findFirstWhere')->with([
            ['user_id', '=', $key->user_id],
            ['server_id', '=', $key->server_id],
        ])->once()->andReturn($key);

        $response = $this->service->handle($key->server_id, $key->user_id, false);
        $this->assertNotEmpty($response);
        $this->assertEquals($key->secret, $response);
    }
}
