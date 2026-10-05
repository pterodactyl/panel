<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Middleware\LanguageMiddlewareTest;

use Pterodactyl\Http\Middleware\LanguageMiddleware;
use Pterodactyl\Models\User;

uses(\Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase::class);
test('language is set for guest', function () {
    $this->setRequestUserModel(null);
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
    expect($this->app->getLocale())->toBe('en');
});
test('language is set with authenticated user', function () {
    $user = User::factory()->make(['language' => 'de']);
    $this->setRequestUserModel($user);
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
    expect($this->app->getLocale())->toBe('de');
});
/**
 * Return an instance of the middleware using the real application.
 */
function getMiddleware(): LanguageMiddleware
{
    return app(LanguageMiddleware::class);
}
