<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Users\CreateUserTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Subusers\CreatesSubusers;
use Pterodactyl\Contracts\Users\CreatesUsers;
use Pterodactyl\Models\User;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    Notification::fake();
});

/** @return array<string, bool|int|string|null> */
function user(array $overrides = []): array
{
    return [...['email' => 'direct@example.com', 'username' => 'direct', 'name_first' => 'Direct', 'name_last' => 'Caller'], ...$overrides];
}

test('users created without the http api meet the same rules', function (array $input, string $field): void {
    User::factory()->create(['email' => 'taken@example.com', 'username' => 'taken', 'external_id' => 'taken']);
    $count = User::query()->count();

    try {
        $this->app->make(CreatesUsers::class)->create(user($input));
        $this->fail('The user was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect(User::query()->count())->toBe($count);
})->with([
    'a missing email' => [['email' => null], 'email'],
    'a malformed email' => [['email' => 'not-an-email'], 'email'],
    'an email starting with a dash' => [['email' => '-x@example.com'], 'email'],
    'a taken email' => [['email' => 'taken@example.com'], 'email'],
    'a taken username' => [['username' => 'taken'], 'username'],
    'a malformed username' => [['username' => '-bad-'], 'username'],
    'a taken external id' => [['external_id' => 'taken'], 'external_id'],
    'a short password' => [['password' => 'abc'], 'password'],
    'an unknown language' => [['language' => 'xx'], 'language'],
    'a root admin flag that is not a boolean' => [['root_admin' => 'yes'], 'root_admin'],
    'a missing first name' => [['name_first' => ''], 'name_first'],
]);

test('values are coerced the way laravel validates them', function (int|string $flag, bool $admin): void {
    $user = $this->app->make(CreatesUsers::class)->create(user(['root_admin' => $flag, 'external_id' => '1024', 'username' => '1024']));

    expect($user->root_admin)->toBe($admin)
        ->and($user->external_id)->toBe('1024')
        ->and($user->username)->toBe('1024');
})->with([[1, true], ['1', true], [0, false], ['0', false]]);

test('a user given a password keeps it, and one without gets a reset link', function (): void {
    $users = $this->app->make(CreatesUsers::class);

    $withPassword = $users->create(user(['password' => 'abcdefgh', 'language' => 'en', 'external_id' => '']));
    expect(Hash::check('abcdefgh', $withPassword->password))->toBeTrue()
        ->and($withPassword->external_id)->toBeNull()
        ->and($withPassword->root_admin)->toBeFalse();
    Notification::assertSentTo($withPassword, AccountCreated::class, fn (AccountCreated $notification): bool => $notification->token === null);

    $withoutPassword = $users->create(user(['email' => 'blank@example.com', 'username' => 'blank', 'password' => '']));
    Notification::assertSentTo($withoutPassword, AccountCreated::class, fn (AccountCreated $notification): bool => $notification->token !== null);
});

test('a subuser invited by email gets an account that meets the user rules', function (): void {
    $server = $this->createServerModel();
    $subusers = $this->app->make(CreatesSubusers::class);

    expect(fn () => $subusers->create($server, '-invite@example.com', ['control.start']))->toThrow(ValidationException::class);
    $this->assertDatabaseMissing('users', ['email' => '-invite@example.com']);

    $subuser = $subusers->create($server, '_new.invite@example.com', ['control.start']);
    expect($subuser->user->wasRecentlyCreated)->toBeTrue()
        ->and($subuser->user->username)->toStartWith('new.invite')
        ->and($subuser->user->root_admin)->toBeFalse();
});
