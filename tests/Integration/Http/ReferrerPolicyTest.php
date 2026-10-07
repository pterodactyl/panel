<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Http\ReferrerPolicyTest;

use Illuminate\Support\Facades\Route;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

uses(HttpTestCase::class);

test('password reset page sends the strict referrer policy', function () {
    // This one route serves both the password reset email and the account setup email.
    $response = $this->get(route('auth.reset', ['token' => 'secret-token']).'?email='.urlencode('user@example.com'));

    $response->assertOk();
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('an ordinary panel page sends the strict referrer policy', function () {
    $response = $this->get(route('auth.login'));

    $response->assertOk();
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('a response that sets its own referrer policy keeps it', function () {
    Route::get('/_test/own-referrer-policy', fn () => response('ok')->header('Referrer-Policy', 'no-referrer'));

    $response = $this->get('/_test/own-referrer-policy');

    $response->assertOk();
    $response->assertHeader('Referrer-Policy', 'no-referrer');
});
