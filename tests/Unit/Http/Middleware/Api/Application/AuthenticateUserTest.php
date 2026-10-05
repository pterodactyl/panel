<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Middleware\Api\Application\AuthenticateUserTest;

use Pterodactyl\Http\Middleware\Api\Application\AuthenticateApplicationUser;
use Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

uses(MiddlewareTestCase::class);
test('no user defined', function () {
    $this->expectException(AccessDeniedHttpException::class);
    $this->setRequestUserModel(null);
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
});
test('non admin user', function () {
    $this->expectException(AccessDeniedHttpException::class);
    $this->generateRequestUserModel(['root_admin' => false]);
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
});
test('admin user', function () {
    $this->generateRequestUserModel(['root_admin' => true]);
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
});
/**
 * Return an instance of the middleware for testing.
 */
function getMiddleware(): AuthenticateApplicationUser
{
    return new AuthenticateApplicationUser();
}
