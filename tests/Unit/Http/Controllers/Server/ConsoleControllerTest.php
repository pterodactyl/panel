<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Http\Controllers\Server;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Server;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Config\Repository;
use Tests\Assertions\ControllerAssertionsTrait;
use Pterodactyl\Http\Controllers\Server\ConsoleController;

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

        if ($function === 'index') {
            $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        }
        $this->config->shouldReceive('get')->with('pterodactyl.console.count')->once()->andReturn(100);
        $this->config->shouldReceive('get')->with('pterodactyl.console.frequency')->once()->andReturn(10);
        $this->controller->shouldReceive('injectJavascript')->once()->andReturnNull();

        $response = $this->controller->$function();
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals($view, $response);
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
