<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Subusers\CreateSubuserTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Subusers\CreatesSubusers;
use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;
use Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionPermissionRegistry;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    Notification::fake();
    $this->app->make(ExtensionPermissionRegistry::class)->register('issue-probe', 'Control the probe.', ['view' => 'View the probe.']);
});

test('subusers created without the http api meet the same rules', function (string $email, array $permissions, string $field): void {
    $server = $this->createServerModel();
    $users = User::query()->count();

    try {
        $this->app->make(CreatesSubusers::class)->create($server, $email, $permissions);
        $this->fail('The subuser was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($server->subusers()->exists())->toBeFalse()
        ->and(User::query()->count())->toBe($users);
})->with([
    'a blank email' => ['', ['control.start'], 'email'],
    'a malformed email' => ['not-an-email', ['control.start'], 'email'],
    'an email starting with a dash' => ['-direct@example.com', ['control.start'], 'email'],
    'an email that is too long' => [str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.com', ['control.start'], 'email'],
    'no permissions' => ['direct@example.com', [], 'permissions'],
    'a permission that is not a string' => ['direct@example.com', [5], 'permissions.0'],
    'a permission nothing defines' => ['direct@example.com', ['control.start', 'power.fake'], 'permissions.1'],
    'a permission of an extension that is not registered' => ['direct@example.com', ['ext.unregistered.view'], 'permissions.0'],
]);

test('a subuser gets the permissions it is given once, extension permissions included', function (): void {
    $server = $this->createServerModel();

    $subuser = $this->app->make(CreatesSubusers::class)->create($server, 'probe@example.com', ['control.start', 'ext.issue-probe.view', 'control.start']);

    expect($subuser->user->wasRecentlyCreated)->toBeTrue()
        ->and($subuser->user->email)->toBe('probe@example.com')
        ->and($subuser->refresh()->permissions)->toBe(['control.start', 'ext.issue-probe.view']);
});

test('an existing user is given access rather than invited', function (): void {
    $server = $this->createServerModel();
    $existing = User::factory()->create();
    $users = User::query()->count();

    $subuser = $this->app->make(CreatesSubusers::class)->create($server, $existing->email, ['control.console']);

    expect($subuser->user->is($existing))->toBeTrue()
        ->and($subuser->user->wasRecentlyCreated)->toBeFalse()
        ->and(User::query()->count())->toBe($users);
});

test('the owner and an existing subuser cannot be added again', function (): void {
    $server = $this->createServerModel();
    $subusers = $this->app->make(CreatesSubusers::class);
    $subuser = $subusers->create($server, 'twice@example.com', ['control.start']);

    expect(fn () => $subusers->create($server, 'twice@example.com', ['control.stop']))->toThrow(ServerSubuserExistsException::class)
        ->and(fn () => $subusers->create($server, $server->user->email, ['control.start']))->toThrow(UserIsServerOwnerException::class)
        ->and(Subuser::query()->where('server_id', $server->id)->count())->toBe(1)
        ->and($subuser->refresh()->permissions)->toBe(['control.start']);
});
