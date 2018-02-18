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
use phpmock\phpunit\PHPMock;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyUpdateServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Carbon\Carbon|\Mockery\Mock
     */
    protected $carbon;

    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->carbon = m::Mock(Carbon::class);
        $this->config = m::mock(Repository::class);
        $this->repository = m::mock(DaemonKeyRepositoryInterface::class);

        $this->service = new DaemonKeyUpdateService($this->carbon, $this->config, $this->repository);
    }

    /**
     * Test that a key is updated.
     */
    public function testKeyIsUpdated()
    {
        $secret = DaemonKeyRepositoryInterface::INTERNAL_KEY_IDENTIFIER . 'random_string';

        $this->getFunctionMock('\\Pterodactyl\\Services\\DaemonKeys', 'str_random')
            ->expects($this->once())->with(40)->willReturn('random_string');

        $this->config->shouldReceive('get')->with('pterodactyl.api.key_expire_time')->once()->andReturn(100);
        $this->carbon->shouldReceive('now')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('addMinutes')->with(100)->once()->andReturnSelf()
            ->shouldReceive('toDateTimeString')->withNoArgs()->once()->andReturn('00:00:00');

        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf();
        $this->repository->shouldReceive('update')->with(123, [
            'secret' => $secret,
            'expires_at' => '00:00:00',
        ])->once()->andReturnNull();

        $response = $this->service->handle(123);
        $this->assertNotEmpty($response);
        $this->assertEquals($secret, $response);
    }
}
