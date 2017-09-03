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

namespace Tests\Unit\Http\Controllers\Server;

use Illuminate\Contracts\Session\Session;
use Mockery as m;
use Pterodactyl\Http\Controllers\Server\ConsoleController;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Tests\Assertions\ControllerAssertionsTrait;
use Tests\TestCase;
use Illuminate\Contracts\Config\Repository;

class ConsoleControllerTest extends TestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Http\Controllers\Server\ConsoleController
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

        $this->config = m::mock(Repository::class);
        $this->session = m::mock(Session::class);

        $this->controller = m::mock(ConsoleController::class, [$this->config, $this->session])->makePartial();
    }

    /**
     * Test both controllers as they do effectively the same thing.
     *
     * @dataProvider controllerDataProvider
     */
    public function testAllControllers($function, $view)
    {
        $server = factory(Server::class)->make();
        $node = factory(Node::class)->make();
        $server->node = $node;

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->config->shouldReceive('get')->with('pterodactyl.console.count')->once()->andReturn(100);
        $this->config->shouldReceive('get')->with('pterodactyl.console.frequency')->once()->andReturn(10);
        $this->controller->shouldReceive('injectJavascript')->once()->andReturnNull();

        $response = $this->controller->$function();
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals($view, $response);
        $this->assertViewHasKey('server', $response);
        $this->assertViewHasKey('node', $response);
        $this->assertViewKeyEquals('server', $server, $response);
        $this->assertViewKeyEquals('node', $node, $response);
    }

    /**
     * Provide data for the tests.
     *
     * @return array
     */
    public function controllerDataProvider()
    {
        return [
            ['index', 'server.index'],
            ['console', 'server.console'],
        ];
    }
}
