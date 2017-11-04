<?php

namespace Tests\Unit\Http\Middleware;

use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Middleware\Authenticate;

class AuthenticateTest extends MiddlewareTestCase
{
    /**
     * Test that a logged in user validates correctly.
     */
    public function testLoggedInUser()
    {
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that a logged out user results in a redirect.
     */
    public function testLoggedOutUser()
    {
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturnNull();
        $this->request->shouldReceive('ajax')->withNoArgs()->once()->andReturn(false);
        $this->request->shouldReceive('expectsJson')->withNoArgs()->once()->andReturn(false);

        $response = $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('auth.login'), $response->getTargetUrl());
    }

    /**
     * Test that a logged out user via an API/Ajax request returns a HTTP error.
     *
     * @expectedException \Illuminate\Auth\AuthenticationException
     */
    public function testLoggedOUtUserApiRequest()
    {
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturnNull();
        $this->request->shouldReceive('ajax')->withNoArgs()->once()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\Authenticate
     */
    private function getMiddleware(): Authenticate
    {
        return new Authenticate();
    }
}
