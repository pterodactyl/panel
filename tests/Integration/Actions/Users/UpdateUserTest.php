<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Users\UpdateUserTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Users\UpdatesUserEmails;
use Pterodactyl\Contracts\Users\UpdatesUserPasswords;
use Pterodactyl\Contracts\Users\UpdatesUsers;
use Pterodactyl\Events\User\PasswordChanged;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    Event::fake([PasswordChanged::class]);
});

test('an update changes only the fields it is given', function (): void {
    $user = User::factory()->create(['external_id' => 'remote-1', 'language' => 'en', 'root_admin' => false]);
    $original = $user->only(['email', 'username', 'name_last', 'password', 'external_id', 'language']);

    $this->app->make(UpdatesUsers::class)->update($user, ['name_first' => 'Renamed', 'root_admin' => '1']);

    $user->refresh();
    expect($user->name_first)->toBe('Renamed')
        ->and($user->root_admin)->toBeTrue()
        ->and($user->only(array_keys($original)))->toBe($original);
    Event::assertNotDispatched(PasswordChanged::class);
});

test('a blank password leaves the password alone, and a new one is hashed', function (): void {
    $user = User::factory()->create();
    $hash = $user->password;
    $users = $this->app->make(UpdatesUsers::class);

    $users->update($user, ['password' => null]);
    $users->update($user, ['password' => '']);
    expect($user->refresh()->password)->toBe($hash);
    Event::assertNotDispatched(PasswordChanged::class);

    $users->update($user, ['password' => 'new-password']);
    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
    Event::assertDispatched(PasswordChanged::class);
});

test('an external id given as null or blank is cleared', function (?string $externalId): void {
    $user = User::factory()->create(['external_id' => 'remote-1']);

    $this->app->make(UpdatesUsers::class)->update($user, ['external_id' => $externalId]);

    expect($user->refresh()->external_id)->toBeNull();
})->with([[null], ['']]);

test('users updated without the http api meet the same rules', function (array $input, string $field): void {
    User::factory()->create(['email' => 'taken@example.com', 'username' => 'taken']);
    $user = User::factory()->create()->refresh();
    $before = $user->getAttributes();

    try {
        $this->app->make(UpdatesUsers::class)->update($user, $input);
        $this->fail('The user was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($user->fresh()->getAttributes())->toBe($before);
})->with([
    'a taken email' => [['email' => 'taken@example.com'], 'email'],
    'a taken username' => [['username' => 'taken'], 'username'],
    'a blank first name' => [['name_first' => ''], 'name_first'],
    'a short password' => [['password' => 'abc'], 'password'],
    'a root admin flag that is not a boolean' => [['root_admin' => 'yes'], 'root_admin'],
    'an unknown language' => [['language' => 'xx'], 'language'],
]);

test('a user keeps its own email, username and external id', function (): void {
    $user = User::factory()->create(['external_id' => 'remote-1']);

    $this->app->make(UpdatesUsers::class)->update($user, ['email' => $user->email, 'username' => $user->username, 'external_id' => 'remote-1']);

    expect($user->refresh()->external_id)->toBe('remote-1');
});

test('the email and password actions meet the user rules', function (): void {
    $taken = User::factory()->create();
    $user = User::factory()->create();

    expect(fn () => $this->app->make(UpdatesUserEmails::class)->update($user, $taken->email))->toThrow(ValidationException::class)
        ->and(fn () => $this->app->make(UpdatesUserPasswords::class)->update($user, 'short'))->toThrow(ValidationException::class);

    $this->app->make(UpdatesUserPasswords::class)->update($user, 'long-enough');
    expect(Hash::check('long-enough', $user->refresh()->password))->toBeTrue();
});
