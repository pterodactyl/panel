<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Http\Controllers\Auth\PasswordResetTest;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Pterodactyl\Events\Auth\FailedPasswordReset;
use Pterodactyl\Events\User\PasswordChanged;
use Pterodactyl\Models\User;
use Pterodactyl\Notifications\SendPasswordReset;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

uses(HttpTestCase::class);
beforeEach(function () {
    Notification::fake();
    Event::fake([FailedPasswordReset::class, PasswordReset::class, PasswordChanged::class]);
});
test('reset link is sent for known email', function () {
    $user = User::factory()->create();
    $this->postJson(route('auth.post.forgot-password'), ['email' => $user->email])->assertOk()->assertJson(['status' => trans('passwords.sent')]);
    Notification::assertSentTo($user, SendPasswordReset::class);
});
test('unknown email returns identical response', function () {
    $this->postJson(route('auth.post.forgot-password'), ['email' => 'unknown@example.com'])->assertOk()->assertJson(['status' => trans('passwords.sent')]);
    Notification::assertNothingSent();
    Event::assertDispatched(fn (FailedPasswordReset $event) => $event->email === 'unknown@example.com');
});
test('email is required for reset link', function () {
    $this->postJson(route('auth.post.forgot-password'), ['email' => 'not-an-email'])->assertUnprocessable();
    $this->postJson(route('auth.post.forgot-password'))->assertUnprocessable();
});
test('password can be reset with valid token', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);
    $this->postJson(route('auth.reset-password'), ['email' => $user->email, 'token' => $token, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertOk()->assertJson(['success' => true, 'redirect_to' => '/', 'send_to_login' => false]);
    $this->assertAuthenticatedAs($user);
    expect(Hash::check('new-password-123', $user->refresh()->password))->toBeTrue();
    Event::assertDispatched(fn (PasswordReset $event) => $event->user->is($user));
    Event::assertDispatched(fn (PasswordChanged $event) => $event->user->is($user));
});
test('two factor account is sent back to login after reset', function () {
    $user = User::factory()->create(['use_totp' => true, 'totp_secret' => encrypt(str_repeat('a', 16))]);
    $token = Password::broker()->createToken($user);
    $this->postJson(route('auth.reset-password'), ['email' => $user->email, 'token' => $token, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertOk()->assertJsonPath('send_to_login', true);
    $this->assertGuest();
    expect(Hash::check('new-password-123', $user->refresh()->password))->toBeTrue();
});
test('reset fails with invalid token', function () {
    $user = User::factory()->create();
    $this->postJson(route('auth.reset-password'), ['email' => $user->email, 'token' => 'invalid-token', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertBadRequest()->assertJsonPath('errors.0.detail', trans('passwords.token'));
    $this->assertGuest();
    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});
test('reset validates password rules', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);
    // Too short.
    $this->postJson(route('auth.reset-password'), ['email' => $user->email, 'token' => $token, 'password' => 'short', 'password_confirmation' => 'short'])->assertUnprocessable();
    // Confirmation mismatch.
    $this->postJson(route('auth.reset-password'), ['email' => $user->email, 'token' => $token, 'password' => 'new-password-123', 'password_confirmation' => 'different-password'])->assertUnprocessable();
    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});
