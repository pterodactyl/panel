<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Middleware\RedirectIfAuthenticatedTest;

use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Middleware\RedirectIfAuthenticated;
use Pterodactyl\Models\User;

uses(\Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase::class);
test('authenticated user is redirected', function () {
    $this->be(User::factory()->make());
    $response = getMiddleware()->handle($this->request, $this->getClosureAssertions());
    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getTargetUrl())->toEqual(route('index'));
});
test('non authenticated user is not redirected', function () {
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
});
/**
 * Return an instance of the middleware using the real auth manager.
 */
function getMiddleware(): RedirectIfAuthenticated
{
    return new RedirectIfAuthenticated;
}
