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

namespace Tests\Unit\Services\Helpers;

use Closure;
use GuzzleHttp\Client;
use Mockery as m;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Tests\TestCase;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class SoftwareVersionServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var object
     */
    protected static $response = [
        'panel' => '0.2.0',
        'daemon' => '0.1.0',
        'discord' => 'https://pterodactyl.io/discord',
    ];

    /**
     * @var \Pterodactyl\Services\Helpers\SoftwareVersionService
     */
    protected $service;

    /**
     * Setup tests
     */
    public function setUp()
    {
        parent::setUp();

        self::$response = (object) self::$response;

        $this->cache = m::mock(CacheRepository::class);
        $this->client = m::mock(Client::class);
        $this->config = m::mock(ConfigRepository::class);

        $this->config->shouldReceive('get')->with('pterodactyl.cdn.cache_time')->once()->andReturn(60);

        $this->cache->shouldReceive('remember')->with(SoftwareVersionService::VERSION_CACHE_KEY, 60, Closure::class)->once()->andReturnNull();

        $this->service = m::mock(SoftwareVersionService::class, [$this->cache, $this->client, $this->config])->makePartial();
    }

    /**
     * Test that the panel version is returned.
     */
    public function testPanelVersionIsReturned()
    {
        $this->cache->shouldReceive('get')->with(SoftwareVersionService::VERSION_CACHE_KEY)->once()->andReturn(self::$response);
        $this->assertEquals(self::$response->panel, $this->service->getPanel());
    }

    /**
     * Test that the panel version is returned as error.
     */
    public function testPanelVersionIsReturnedAsErrorIfNoKeyIsFound()
    {
        $this->cache->shouldReceive('get')->with(SoftwareVersionService::VERSION_CACHE_KEY)->once()->andReturn((object) []);
        $this->assertEquals('error', $this->service->getPanel());
    }

    /**
     * Test that the daemon version is returned.
     */
    public function testDaemonVersionIsReturned()
    {
        $this->cache->shouldReceive('get')->with(SoftwareVersionService::VERSION_CACHE_KEY)->once()->andReturn(self::$response);
        $this->assertEquals(self::$response->daemon, $this->service->getDaemon());
    }

    /**
     * Test that the daemon version is returned as an error.
     */
    public function testDaemonVersionIsReturnedAsErrorIfNoKeyIsFound()
    {
        $this->cache->shouldReceive('get')->with(SoftwareVersionService::VERSION_CACHE_KEY)->once()->andReturn((object) []);
        $this->assertEquals('error', $this->service->getDaemon());
    }

    /**
     * Test that the discord URL is returned.
     */
    public function testDiscordUrlIsReturned()
    {
        $this->cache->shouldReceive('get')->with(SoftwareVersionService::VERSION_CACHE_KEY)->once()->andReturn(self::$response);
        $this->assertEquals(self::$response->discord, $this->service->getDiscord());
    }

    /**
     * Test that the correct boolean value is returned by the helper for each version passed.
     *
     * @dataProvider panelVersionProvider
     */
    public function testCorrectBooleanValueIsReturnedWhenCheckingPanelVersion($version, $response)
    {
        $this->config->shouldReceive('get')->with('app.version')->andReturn($version);
        $this->service->shouldReceive('getPanel')->withNoArgs()->andReturn(self::$response->panel);

        $this->assertEquals($response, $this->service->isLatestPanel());
    }

    /**
     * Test that the correct boolean value is returned.
     *
     * @dataProvider daemonVersionProvider
     */
    public function testCorrectBooleanValueIsReturnedWhenCheckingDaemonVersion($version, $response)
    {
        $this->service->shouldReceive('getDaemon')->withNoArgs()->andReturn(self::$response->daemon);

        $this->assertEquals($response, $this->service->isLatestDaemon($version));
    }

    /**
     * Provide data for testing boolean response on panel version.
     *
     * @return array
     */
    public function panelVersionProvider()
    {
        return [
            [self::$response['panel'], true],
            ['0.0.1', false],
            ['canary', true],
        ];
    }

    /**
     * Provide data for testing booklean response for daemon version.
     *
     * @return array
     */
    public function daemonVersionProvider()
    {
        return [
            [self::$response['daemon'], true],
            ['0.0.1', false],
            ['0.0.0-canary', true],
        ];
    }
}
