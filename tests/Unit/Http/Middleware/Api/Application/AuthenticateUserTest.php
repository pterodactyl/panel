<?php

namespace Tests\Unit\Http\Middleware\API\Application;

use Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Http\Middleware\Api\Application\AuthenticateApplicationUser;

class AuthenticateUserTest extends MiddlewareTestCase
{
    /**
     * Test that no user defined results in an access denied exception.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testNoUserDefined()
    {
        $this->setRequestUserModel(null);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that a non-admin user results an an exception.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testNonAdminUser()
    {
        $this->generateRequestUserModel(['root_admin' => false]);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an admin user continues though the middleware.
     */
    public function testAdminUser()
    {
        $this->generateRequestUserModel(['root_admin' => true]);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware for testing.
     *
     * @return \Pterodactyl\Http\Middleware\Api\Application\AuthenticateApplicationUser
     */
    private function getMiddleware(): AuthenticateApplicationUser
    {
        return new AuthenticateApplicationUser;
    }
}
