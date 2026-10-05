<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Users\CompleteLoginTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Pterodactyl\Contracts\Users\CompletesLogins;
use Pterodactyl\Data\LoginCheckpoint;
use Pterodactyl\Events\ActivityLogged;
use Pterodactyl\Events\Auth\DirectLogin;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
beforeEach(function () {
    Event::fake([DirectLogin::class, ActivityLogged::class]);
});
test('account without two factor is signed in and remembered', function () {
    $user = User::factory()->create();
    Session::put('auth_confirmation_token', LoginCheckpoint::issue($user->id));
    $result = $this->app->make(CompletesLogins::class)->complete($user);
    expect($result->complete)->toBeTrue();
    expect($result->intended)->toBe('/');
    expect($result->toResponseData()['user']['uuid'])->toBe($user->uuid);
    expect(Session::has('auth_confirmation_token'))->toBeFalse();
    expect($user->refresh()->remember_token)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
    Event::assertDispatched(fn (DirectLogin $event) => $event->user->is($user) && $event->remember);
});
test('account with two factor receives a checkpoint instead of a session', function () {
    $user = User::factory()->create(['use_totp' => true, 'totp_secret' => encrypt(str_repeat('a', 16))]);
    $logins = $this->app->make(CompletesLogins::class);
    $result = $logins->complete($user);
    expect($result->complete)->toBeFalse();
    expect($result->confirmationToken)->toBeString()->toHaveLength(64);
    $checkpoint = $logins->pendingCheckpoint();
    expect($checkpoint)->toBeInstanceOf(LoginCheckpoint::class);
    expect($checkpoint->userId)->toBe($user->id);
    expect($checkpoint->matches($result->confirmationToken))->toBeTrue();
    expect($checkpoint->expiresAt->equalTo(now()->addMinutes(5)))->toBeTrue();
    $this->assertGuest();
    $this->assertActivityFor('auth:checkpoint', null, $user);
    Event::assertNotDispatched(DirectLogin::class);
    $this->travel(5)->minutes();
    expect($logins->pendingCheckpoint())->not->toBeNull();
    $this->travel(1)->seconds();
    expect($logins->pendingCheckpoint())->toBeNull();
});
test('establishing a session discards the pending checkpoint', function () {
    $user = User::factory()->create(['use_totp' => true, 'totp_secret' => encrypt(str_repeat('a', 16))]);
    $logins = $this->app->make(CompletesLogins::class);
    $logins->complete($user);
    $result = $logins->establish($user);
    expect($result->complete)->toBeTrue();
    expect($logins->pendingCheckpoint())->toBeNull();
    $this->assertAuthenticatedAs($user);
    Event::assertDispatched(DirectLogin::class);
});
test('login can be completed from a web route outside the core controllers', function () {
    $plain = User::factory()->create();
    $protected = User::factory()->create(['use_totp' => true, 'totp_secret' => encrypt(str_repeat('a', 16))]);
    Route::middleware('web')->get('/api/_test/external-login/{user}', function (CompletesLogins $logins, User $user) {
        $result = $logins->complete($user);

        return redirect($result->complete ? $result->intended : '/auth/login?checkpoint=1');
    });
    $this->get('/api/_test/external-login/'.$protected->uuid)->assertRedirect('/auth/login?checkpoint=1')->assertSessionHas('auth_confirmation_token');
    $this->assertGuest();
    $this->get('/api/_test/external-login/'.$plain->uuid)->assertRedirect('/')->assertSessionMissing('auth_confirmation_token');
    $this->assertAuthenticatedAs($plain);
});
