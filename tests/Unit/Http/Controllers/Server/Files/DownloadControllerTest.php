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

namespace Tests\Unit\Http\Controllers\Server\Files;

use Mockery as m;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Session\Session;
use Tests\Assertions\ControllerAssertionsTrait;
use Pterodactyl\Http\Controllers\Server\Files\DownloadController;

class DownloadControllerTest extends TestCase
{
    use ControllerAssertionsTrait, PHPMock;

    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Pterodactyl\Http\Controllers\Server\Files\DownloadController
     */
    protected $controller;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->cache = m::mock(Repository::class);
        $this->session = m::mock(Session::class);

        $this->controller = m::mock(DownloadController::class, [$this->cache, $this->session])->makePartial();
    }

    /**
     * Test the download controller redirects correctly.
     */
    public function testIndexController()
    {
        $server = factory(Server::class)->make();
        $node = factory(Node::class)->make();
        $server->node = $node;

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('download-files', $server)->once()->andReturnNull();
        $this->getFunctionMock('\\Pterodactyl\\Http\\Controllers\\Server\\Files', 'str_random')
            ->expects($this->once())->willReturn('randomString');

        $this->cache->shouldReceive('tags')->with(['Server:Downloads'])->once()->andReturnSelf()
            ->shouldReceive('put')->with('randomString', ['server' => $server->uuid, 'path' => '/my/file.txt'], 5)->once()->andReturnNull();

        $response = $this->controller->index('1234', '/my/file.txt');
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectUrlEquals(sprintf(
            '%s://%s:%s/server/file/download/%s', $server->node->scheme, $server->node->fqdn, $server->node->daemonListen, 'randomString'
        ), $response);
    }
}
