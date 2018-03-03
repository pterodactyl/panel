<?php

namespace Tests\Unit\Http\Middleware\API;

use Mockery as m;
use Barryvdh\Debugbar\LaravelDebugbar;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Http\Middleware\Api\SetSessionDriver;

class SetSessionDriverTest extends MiddlewareTestCase
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application|\Mockery\Mock
     */
    private $appMock;

    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    private $config;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->appMock = m::mock(Application::class);
        $this->config = m::mock(Repository::class);
    }

    /**
     * Test that a production environment does not try to disable debug bar.
     */
    public function testProductionEnvironment()
    {
        $this->appMock->shouldReceive('environment')->withNoArgs()->once()->andReturn('production');
        $this->config->shouldReceive('set')->with('session.driver', 'array')->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that a local environment does disable debug bar.
     */
    public function testLocalEnvironment()
    {
        $this->appMock->shouldReceive('environment')->withNoArgs()->once()->andReturn('local');
        $this->appMock->shouldReceive('make')->with(LaravelDebugbar::class)->once()->andReturnSelf();
        $this->appMock->shouldReceive('disable')->withNoArgs()->once()->andReturnNull();

        $this->config->shouldReceive('set')->with('session.driver', 'array')->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware with mocked dependencies for testing.
     *
     * @return \Pterodactyl\Http\Middleware\Api\SetSessionDriver
     */
    private function getMiddleware(): SetSessionDriver
    {
        return new SetSessionDriver($this->appMock, $this->config);
    }
}
