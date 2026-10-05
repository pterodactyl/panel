<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Actions\Users\CompleteLoginTest;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Mockery;
use Pterodactyl\Actions\Users\CompleteLogin;
use Pterodactyl\Contracts\Users\CompletesLogins;
use Pterodactyl\Data\LoginCheckpoint;
use Pterodactyl\Data\LoginResult;
use Pterodactyl\Events\Auth\DirectLogin;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('session.driver', 'array');
    Event::fake([DirectLogin::class]);
});

function user(bool $twoFactor = false): User
{
    return User::factory()->make(['use_totp' => $twoFactor])->forceFill(['id' => 7])->syncOriginal();
}

function expectSessionLogin(User $user): void
{
    $guard = Mockery::mock(StatefulGuard::class);
    $guard->shouldReceive('login')->once()->with($user, true);
    Auth::shouldReceive('guard')->once()->withNoArgs()->andReturn($guard);
}

test('the action resolves from its contract', function (): void {
    expect($this->app->make(CompletesLogins::class))->toBeInstanceOf(CompleteLogin::class);
});

test('an account without two-factor is signed in immediately', function (): void {
    $user = user();
    expectSessionLogin($user);
    Session::put('auth_confirmation_token', LoginCheckpoint::issue(99));
    $previous = Session::getId();

    $result = $this->app->make(CompletesLogins::class)->complete($user);

    expect($result->complete)->toBeTrue()
        ->and($result->user)->toBe($user)
        ->and($result->intended)->toBe('/')
        ->and($result->confirmationToken)->toBeNull()
        ->and($result->toResponseData())->toBe(['complete' => true, 'intended' => '/', 'user' => $user->toVueObject()])
        ->and(Session::has('auth_confirmation_token'))->toBeFalse()
        ->and(Session::getId())->not->toBe($previous);
    Event::assertDispatched(fn (DirectLogin $event): bool => $event->user === $user && $event->remember);
});

test('establishing a session signs in an account that uses two-factor', function (): void {
    $user = user(twoFactor: true);
    expectSessionLogin($user);
    Session::put('auth_confirmation_token', LoginCheckpoint::issue($user->id));

    $result = $this->app->make(CompletesLogins::class)->establish($user);

    expect($result->complete)->toBeTrue()
        ->and($this->app->make(CompletesLogins::class)->pendingCheckpoint())->toBeNull();
    Event::assertDispatched(DirectLogin::class);
});

test('a pending checkpoint is only reported while it can be confirmed', function (string $case, bool $pending): void {
    $stored = match ($case) {
        'unexpired' => new LoginCheckpoint(7, 'token', CarbonImmutable::now()->addMinute()),
        'expiring now' => new LoginCheckpoint(7, 'token', CarbonImmutable::now()),
        'expired' => new LoginCheckpoint(7, 'token', CarbonImmutable::now()->subSecond()),
        'array payload' => ['user_id' => 7, 'token_value' => 'token', 'expires_at' => CarbonImmutable::now()->addMinute()],
        'unrelated value' => 'token',
        default => null,
    };
    if ($stored !== null) {
        Session::put('auth_confirmation_token', $stored);
    }

    $checkpoint = $this->app->make(CompletesLogins::class)->pendingCheckpoint();

    $pending
        ? expect($checkpoint)->toBe($stored)
        : expect($checkpoint)->toBeNull();
})->with([
    ['nothing stored', false],
    ['unexpired', true],
    ['expiring now', true],
    ['expired', false],
    ['array payload', false],
    ['unrelated value', false],
]);

test('checkpoints are issued for five minutes with an unguessable token', function (): void {
    $first = LoginCheckpoint::issue(7);
    $second = LoginCheckpoint::issue(7);

    expect($first->userId)->toBe(7)
        ->and($first->token)->toHaveLength(64)
        ->and($second->token)->not->toBe($first->token)
        ->and($first->expiresAt->equalTo(CarbonImmutable::now()->addMinutes(5)))->toBeTrue()
        ->and($first->matches($first->token))->toBeTrue()
        ->and($first->matches($second->token))->toBeFalse()
        ->and($first->matches(''))->toBeFalse();
});

test('a checkpoint result exposes only the confirmation token', function (): void {
    $checkpoint = LoginCheckpoint::issue(7);
    $result = LoginResult::checkpoint(user(twoFactor: true), $checkpoint, '/');

    expect($result->complete)->toBeFalse()
        ->and($result->confirmationToken)->toBe($checkpoint->token)
        ->and($result->toResponseData())->toBe(['complete' => false, 'confirmation_token' => $checkpoint->token]);
});
