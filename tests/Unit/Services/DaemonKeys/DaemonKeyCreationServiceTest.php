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

namespace Tests\Unit\Services\DaemonKeys;

use Mockery as m;
use Carbon\Carbon;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyCreationServiceTest extends TestCase
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
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->carbon = m::mock(Carbon::class);
        $this->config = m::Mock(Repository::class);
        $this->repository = m::mock(DaemonKeyRepositoryInterface::class);

        $this->service = new DaemonKeyCreationService($this->carbon, $this->config, $this->repository);
    }

    /**
     * Test that a daemon key is created.
     */
    public function testDaemonKeyIsCreated()
    {
        $this->getFunctionMock('\\Pterodactyl\\Services\\DaemonKeys', 'str_random')
            ->expects($this->once())->willReturn('random_string');

        $this->config->shouldReceive('get')->with('pterodactyl.api.key_expire_time')->once()->andReturn(100);
        $this->carbon->shouldReceive('now')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('addMinutes')->with(100)->once()->andReturnSelf()
            ->shouldReceive('toDateTimeString')->withNoArgs()->once()->andReturn('00:00:00');

        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('create')->with([
                'user_id' => 1,
                'server_id' => 2,
                'secret' => DaemonKeyRepositoryInterface::INTERNAL_KEY_IDENTIFIER . 'random_string',
                'expires_at' => '00:00:00',
            ])->once()->andReturnNull();

        $response = $this->service->handle(2, 1);
        $this->assertNotEmpty($response);
        $this->assertEquals('i_random_string', $response);
    }
}
