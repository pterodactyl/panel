<?php

namespace Tests\Unit\Http\Middleware;

use Mockery as m;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Http\Middleware\LanguageMiddleware;

class LanguageMiddlewareTest extends MiddlewareTestCase
{
    /**
     * @var \Illuminate\Foundation\Application|\Mockery\Mock
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
     * Test that a language is defined via the middleware.
     */
    public function testLanguageIsSet()
    {
        $this->config->shouldReceive('get')->with('app.locale', 'en')->once()->andReturn('en');
        $this->appMock->shouldReceive('setLocale')->with('en')->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\LanguageMiddleware
     */
    private function getMiddleware(): LanguageMiddleware
    {
        return new LanguageMiddleware($this->appMock, $this->config);
    }
}
