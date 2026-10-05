<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Console\Commands\User\MakeUserCommandTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
beforeEach(function (): void {
    Notification::fake();
});
test('rejects a password shorter than eight characters', function (): void {
    $this->artisan('p:user:make', userOptions('abc'))
        ->expectsOutputToContain('The password must be at least 8 characters.')
        ->assertExitCode(1);

    $this->assertDatabaseMissing('users', ['username' => 'cli-user']);
});
test('creates a user with an eight character password', function (): void {
    $this->artisan('p:user:make', userOptions('abcdefgh'))->assertExitCode(0);

    $user = User::query()->where('username', 'cli-user')->firstOrFail();
    expect(Hash::check('abcdefgh', $user->password))->toBeTrue();

    $log = $user->activity()->where('event', 'user:user.create')->sole();
    expect($log->actor_id)->toBeNull()
        ->and($log->properties->get('email'))->toBe('cli-user@example.com');
});
test('creates a user without a password when asked to', function (): void {
    $options = userOptions('');
    unset($options['--password']);

    $this->artisan('p:user:make', [...$options, '--no-password' => true])->assertExitCode(0);

    $this->assertDatabaseHas('users', ['username' => 'cli-user']);
});
/**
 * @return array<string, bool|string>
 */
function userOptions(string $password): array
{
    return [
        '--email' => 'cli-user@example.com',
        '--username' => 'cli-user',
        '--name-first' => 'Cli',
        '--name-last' => 'User',
        '--password' => $password,
        '--admin' => '0',
        '--no-interaction' => true,
    ];
}
