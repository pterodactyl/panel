<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Middleware\LanguageMiddlewareTest;

use Pterodactyl\Http\Middleware\LanguageMiddleware;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Translations\LanguageRegistry;

uses(\Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase::class);
test('only the core english translations are installed', function () {
    expect(app(LanguageRegistry::class)->available())->toBe(['en' => 'English']);
});
test('language is set for guest', function () {
    $this->setRequestUserModel(null);
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
    expect($this->app->getLocale())->toBe('en');
});
test('language is set with authenticated user', function () {
    $this->setRequestUserModel(User::factory()->make(['language' => 'en']));
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
    expect($this->app->getLocale())->toBe('en');
});
test('a user without a language follows the panel default', function () {
    $this->setRequestUserModel(User::factory()->make(['language' => null]));
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
    expect($this->app->getLocale())->toBe('en');
});
test('a language no pack provides falls back to the panel default', function () {
    $this->setRequestUserModel(User::factory()->make(['language' => 'de']));
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
    expect($this->app->getLocale())->toBe('en');
});
test('a panel default no pack provides falls back to english', function () {
    config(['app.locale' => 'de']);
    $this->setRequestUserModel(null);
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
    expect($this->app->getLocale())->toBe('en');
});
function getMiddleware(): LanguageMiddleware
{
    return app(LanguageMiddleware::class);
}
