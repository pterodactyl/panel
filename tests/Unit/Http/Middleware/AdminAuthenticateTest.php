<?php

namespace Tests\Unit\Http\Middleware;

use Pterodactyl\Models\User;
use Pterodactyl\Http\Middleware\AdminAuthenticate;

class AdminAuthenticateTest extends MiddlewareTestCase
{
    /**
     * Test that an admin is authenticated.
     */
    public function testAdminsAreAuthenticated()
    {
        $user = factory(User::class)->make(['root_admin' => 1]);

        $this->request->shouldReceive('user')->withNoArgs()->twice()->andReturn($user);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that a missing user in the request triggers an error.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testExceptionIsThrownIfUserDoesNotExist()
    {
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an exception is thrown if the user is not an admin.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testExceptionIsThrownIfUserIsNotAnAdmin()
    {
        $user = factory(User::class)->make(['root_admin' => 0]);

        $this->request->shouldReceive('user')->withNoArgs()->twice()->andReturn($user);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\AdminAuthenticate
     */
    private function getMiddleware(): AdminAuthenticate
    {
        return new AdminAuthenticate();
    }
}
