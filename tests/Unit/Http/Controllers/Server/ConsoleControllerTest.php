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
use Pterodactyl\Models\Server;
use Illuminate\Contracts\Config\Repository;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Http\Controllers\Server\ConsoleController;

class ConsoleControllerTest extends ControllerTestCase
{
    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    protected $config;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
    }

    /**
     * Test both controllers as they do effectively the same thing.
     *
     * @dataProvider controllerDataProvider
     */
    public function testAllControllers($function, $view)
    {
        $controller = $this->getController();
        $server = factory(Server::class)->make();
        $this->setRequestAttribute('server', $server);
        $this->mockInjectJavascript();

        $this->config->shouldReceive('get')->with('pterodactyl.console.count')->once()->andReturn(100);
        $this->config->shouldReceive('get')->with('pterodactyl.console.frequency')->once()->andReturn(10);

        $response = $controller->$function($this->request);
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

    /**
     * Return a mocked instance of the controller to allow access to authorization functionality.
     *
     * @return \Pterodactyl\Http\Controllers\Server\ConsoleController|\Mockery\Mock
     */
    private function getController()
    {
        return $this->buildMockedController(ConsoleController::class, [$this->config]);
    }
}
