<?php

namespace Tests\Unit\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Middleware\RedirectIfAuthenticated;

class RedirectIfAuthenticatedTest extends MiddlewareTestCase
{
    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that an authenticated user is redirected.
     */
    public function testAuthenticatedUserIsRedirected()
    {
        Auth::shouldReceive('guard')->with(null)->once()->andReturnSelf();
        Auth::shouldReceive('check')->withNoArgs()->once()->andReturn(true);

        $response = $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(route('index'), $response->getTargetUrl());
    }

    /**
     * Test that a non-authenticated user continues through the middleware.
     */
    public function testNonAuthenticatedUserIsNotRedirected()
    {
        Auth::shouldReceive('guard')->with(null)->once()->andReturnSelf();
        Auth::shouldReceive('check')->withNoArgs()->once()->andReturn(false);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \App\Http\Middleware\RedirectIfAuthenticated
     */
    private function getMiddleware(): RedirectIfAuthenticated
    {
        return new RedirectIfAuthenticated();
    }
}
