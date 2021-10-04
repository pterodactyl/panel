<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware\Api;

use Mockery as m;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Http\Middleware\Api\SetSessionDriver;
use Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase;

class SetSessionDriverTest extends MiddlewareTestCase
{
    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    private $config;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
    }

    /**
     * Test that a production environment does not try to disable debug bar.
     */
    public function testMiddleware()
    {
        $this->config->shouldReceive('set')->once()->with('session.driver', 'array')->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware with mocked dependencies for testing.
     */
    private function getMiddleware(): SetSessionDriver
    {
        return new SetSessionDriver($this->config);
    }
}
