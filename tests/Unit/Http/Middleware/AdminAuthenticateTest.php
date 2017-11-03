<?php

namespace Tests\Unit\Http\Middleware;

use Pterodactyl\Models\User;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
     */
    public function testExceptionIsThrownIfUserDoesNotExist()
    {
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturnNull();

        try {
            $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        } catch (HttpException $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
        }
    }

    /**
     * Test that an exception is thrown if the user is not an admin.
     */
    public function testExceptionIsThrownIfUserIsNotAnAdmin()
    {
        $user = factory(User::class)->make(['root_admin' => 0]);

        $this->request->shouldReceive('user')->withNoArgs()->twice()->andReturn($user);

        try {
            $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        } catch (HttpException $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
        }
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
