<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Http\Controllers\Auth\LoginControllerTest;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Pterodactyl\Data\LoginCheckpoint;
use Pterodactyl\Events\ActivityLogged;
use Pterodactyl\Events\Auth\DirectLogin;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

uses(HttpTestCase::class);
beforeEach(function () {
    Event::fake([Failed::class, DirectLogin::class, ActivityLogged::class]);
});
test('auth pages render the react shell', function () {
    foreach (['/auth/login', '/auth/password', '/auth/password/reset/some-token', '/auth/anything-else'] as $uri) {
        $this->get($uri)->assertOk()->assertViewIs('templates.auth.core');
    }
});
test('user can log in with username', function () {
    $user = User::factory()->create();
    $this->postJson(route('auth.login'), ['user' => $user->username, 'password' => 'password'])->assertOk()->assertJsonPath('data.complete', true)->assertJsonPath('data.intended', '/')->assertJsonPath('data.user.uuid', $user->uuid);
    $this->assertAuthenticatedAs($user);
    Event::assertDispatched(fn (DirectLogin $event) => $event->user->is($user) && $event->remember);
});
test('user can log in with email', function () {
    $user = User::factory()->create();
    $this->postJson(route('auth.login'), ['user' => $user->email, 'password' => 'password'])->assertOk()->assertJsonPath('data.complete', true);
    $this->assertAuthenticatedAs($user);
});
test('login fails with invalid password', function () {
    $user = User::factory()->create();
    $this->postJson(route('auth.login'), ['user' => $user->username, 'password' => 'wrong'])->assertBadRequest()->assertJsonPath('errors.0.detail', trans('auth.failed'));
    $this->assertGuest();
    Event::assertDispatched(fn (Failed $event) => $event->guard === 'auth' && $event->user->is($user));
    Event::assertNotDispatched(DirectLogin::class);
});
test('login fails with unknown user', function () {
    $this->postJson(route('auth.login'), ['user' => 'does-not-exist', 'password' => 'password'])->assertBadRequest()->assertJsonPath('errors.0.detail', trans('auth.failed'));
    $this->assertGuest();
    Event::assertDispatched(fn (Failed $event) => $event->guard === 'auth' && is_null($event->user));
});
test('unknown user and bad password responses are identical', function () {
    $user = User::factory()->create();
    $unknown = $this->postJson(route('auth.login'), ['user' => 'does-not-exist', 'password' => 'password']);
    $badPassword = $this->postJson(route('auth.login'), ['user' => $user->username, 'password' => 'wrong']);
    expect($badPassword->getStatusCode())->toBe($unknown->getStatusCode());
    expect($badPassword->json())->toBe($unknown->json());
});
test('unknown user login still performs a password hash', function () {
    Hash::spy();
    $this->postJson(route('auth.login'), ['user' => 'does-not-exist', 'password' => 'password'])->assertBadRequest();
    Hash::shouldHaveReceived('make')->once()->with('password');
});
test('unknown user and existing user responses are identical without a valid password', function (array $payload) {
    $user = User::factory()->create();
    $unknown = $this->postJson(route('auth.login'), ['user' => 'does-not-exist'] + $payload);
    $existing = $this->postJson(route('auth.login'), ['user' => $user->username] + $payload);
    $unknown->assertUnprocessable();
    expect($existing->getStatusCode())->toBe($unknown->getStatusCode());
    expect($existing->json())->toBe($unknown->json());
})->with([
    'missing password' => [[]],
    'non-string password' => [['password' => ['array']]],
]);
test('login with two factor enabled returns confirmation token', function () {
    $user = User::factory()->create(['use_totp' => true, 'totp_secret' => encrypt(str_repeat('a', 16))]);
    $response = $this->postJson(route('auth.login'), ['user' => $user->username, 'password' => 'password']);
    $response->assertOk()->assertJsonPath('data.complete', false)->assertSessionHas('auth_confirmation_token');
    $this->assertGuest();
    $token = $response->json('data.confirmation_token');
    expect($token)->toBeString();
    expect(session('auth_confirmation_token'))->toBeInstanceOf(LoginCheckpoint::class)->and(session('auth_confirmation_token')->token)->toBe($token)->and(session('auth_confirmation_token')->userId)->toBe($user->id);
});
test('login is locked out after repeated failures', function () {
    $user = User::factory()->create();
    for ($i = 0; $i < config('auth.lockout.attempts'); $i++) {
        $this->postJson(route('auth.login'), ['user' => $user->username, 'password' => 'wrong'])->assertBadRequest();
    }
    $this->postJson(route('auth.login'), ['user' => $user->username, 'password' => 'password'])->assertTooManyRequests();
    $this->assertGuest();
});
test('user can log out', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson(route('auth.logout'))->assertNoContent();
    $this->assertGuest();
});
test('logout requires authentication', function () {
    $this->postJson(route('auth.logout'))->assertUnauthorized();
});
test('two factor checkpoint is logged and can be completed through the checkpoint endpoint', function () {
    $user = User::factory()->create(['use_totp' => true, 'totp_secret' => encrypt(str_repeat('a', 16))]);
    $token = $this->postJson(route('auth.login'), ['user' => $user->username, 'password' => 'password'])->assertOk()->json('data.confirmation_token');
    $this->assertActivityLogged('auth:checkpoint');
    Event::assertNotDispatched(DirectLogin::class);
    $totp = $this->app->make(Google2FA::class)->getCurrentOtp(str_repeat('a', 16));
    $this->postJson(route('auth.login-checkpoint'), ['confirmation_token' => $token, 'authentication_code' => $totp])->assertOk()->assertJsonPath('data.complete', true)->assertSessionMissing('auth_confirmation_token');
    $this->assertAuthenticatedAs($user);
});
test('successful login clears the lockout counter', function () {
    $user = User::factory()->create();
    for ($i = 0; $i < config('auth.lockout.attempts') - 1; $i++) {
        $this->postJson(route('auth.login'), ['user' => $user->username, 'password' => 'wrong'])->assertBadRequest();
    }
    $this->postJson(route('auth.login'), ['user' => $user->username, 'password' => 'password'])->assertOk();
    $this->postJson(route('auth.logout'))->assertNoContent();
    $this->postJson(route('auth.login'), ['user' => $user->username, 'password' => 'wrong'])->assertBadRequest();
});
