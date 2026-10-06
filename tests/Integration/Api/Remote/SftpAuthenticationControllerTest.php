<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Remote\SftpAuthenticationControllerTest;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use phpseclib3\Crypt\EC;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

use function pterodactylTestCase;

uses(IntegrationTestCase::class);
/**
 * Sets up the tests.
 */
beforeEach(function () {
    [$user, $server] = $this->generateTestAccount();
    $user->update(['password' => 'foobar']);
    $this->user = $user;
    $this->server = $server;
    setAuthorization();
});
dataset('authorizationTypeDataProvider', function () {
    return ['password auth' => ['password'], 'public key auth' => ['public_key']];
});
dataset('serverStateDataProvider', function () {
    return ['installing' => [Server::STATUS_INSTALLING], 'suspended' => [Server::STATUS_SUSPENDED], 'restoring a backup' => [Server::STATUS_RESTORING_BACKUP]];
});
test('public key is validated correctly', function () {
    $key = UserSSHKey::factory()->for($this->user)->create();
    $this->postJson('/api/remote/sftp/auth', [])->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'username')->assertJsonPath('errors.0.meta.rule', 'required')->assertJsonPath('errors.1.meta.source_field', 'password')->assertJsonPath('errors.1.meta.rule', 'required');
    $data = ['type' => 'public_key', 'username' => getUsername(), 'password' => $key->public_key];
    $this->postJson('/api/remote/sftp/auth', $data)->assertOk()->assertJsonPath('server', $this->server->uuid)->assertJsonPath('permissions', ['*']);
    $key->delete();
    $this->postJson('/api/remote/sftp/auth', $data)->assertForbidden();
    $this->postJson('/api/remote/sftp/auth', array_merge($data, ['type' => null]))->assertForbidden();
});
test('password is validated correctly', function () {
    $this->postJson('/api/remote/sftp/auth', ['username' => getUsername(), 'password' => ''])->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'password')->assertJsonPath('errors.0.meta.rule', 'required');
    $this->postJson('/api/remote/sftp/auth', ['username' => getUsername(), 'password' => 'wrong password'])->assertForbidden();
    $this->user->update(['password' => 'foobar']);
    $this->postJson('/api/remote/sftp/auth', ['username' => getUsername(), 'password' => 'foobar'])->assertOk();
});
test('user is throttled if invalid credentials are provided', function () {
    $limit = (int) config('auth.lockout.attempts');
    for ($i = 0; $i <= $limit; $i++) {
        $this->postJson('/api/remote/sftp/auth', ['type' => 'public_key', 'username' => getUsername(), 'password' => 'invalid key'])->assertStatus($i === $limit ? 429 : 403);
    }
});
test('wrong password for a real user on a real server is logged and counts toward the lockout', function () {
    $limit = (int) config('auth.lockout.attempts');
    for ($i = 0; $i <= $limit; $i++) {
        $this->postJson('/api/remote/sftp/auth', ['username' => getUsername(), 'password' => 'wrong password'])->assertStatus($i === $limit ? 429 : 403);
    }
    expect(failedSftpLogins($this->user)->count())->toBe($limit);
    expect(failedSftpLogins($this->user)->first()->properties['method'])->toBe('password');
});
test('user is not throttled if no public key matches', function () {
    for ($i = 0; $i <= 10; $i++) {
        $this->postJson('/api/remote/sftp/auth', ['type' => 'public_key', 'username' => getUsername(), 'password' => EC::createKey('Ed25519')->getPublicKey()->toString('OpenSSH')])->assertForbidden();
    }
});
test('unknown user is not throttled if no public key matches', function () {
    for ($i = 0; $i <= 10; $i++) {
        $this->postJson('/api/remote/sftp/auth', ['type' => 'public_key', 'username' => 'does-not-exist.'.$this->server->uuidShort, 'password' => EC::createKey('Ed25519')->getPublicKey()->toString('OpenSSH')])->assertForbidden();
    }
});
test('unknown user password login still performs a password hash', function () {
    Hash::spy();
    $this->postJson('/api/remote/sftp/auth', ['username' => 'does-not-exist.'.$this->server->uuidShort, 'password' => 'foobar'])->assertForbidden();
    Hash::shouldHaveReceived('make')->once()->with('foobar');
});
test('unknown server is rejected without hashing, lockout or activity', function () {
    Hash::spy();
    $limit = (int) config('auth.lockout.attempts');
    for ($i = 0; $i <= $limit + 2; $i++) {
        $this->postJson('/api/remote/sftp/auth', ['username' => $this->user->username.'.doesnotexist', 'password' => 'wrong password'])->assertForbidden();
    }
    Hash::shouldNotHaveReceived('check');
    Hash::shouldNotHaveReceived('make');
    expect(failedSftpLogins($this->user)->count())->toBe(0);
});
test('unknown username on an unknown server does not hash either', function () {
    Hash::spy();
    $this->postJson('/api/remote/sftp/auth', ['username' => 'does-not-exist.doesnotexist', 'password' => 'foobar'])->assertForbidden();
    Hash::shouldNotHaveReceived('check');
    Hash::shouldNotHaveReceived('make');
});
test('rotating made-up server identifiers never reaches a password check or throttles', function () {
    Hash::spy();
    $limit = (int) config('auth.lockout.attempts');
    for ($i = 0; $i <= $limit * 3; $i++) {
        $this->postJson('/api/remote/sftp/auth', ['username' => $this->user->username.'.fake'.$i, 'password' => 'wrong password'])->assertForbidden();
    }
    Hash::shouldNotHaveReceived('check');
    expect(failedSftpLogins($this->user)->count())->toBe(0);
});
test('an x509 certificate offered as a public key is rejected and throttled', function (): void {
    $certificate = file_get_contents(base_path('tests/Fixtures/keys/x509-certificate.pem'));
    $limit = (int) config('auth.lockout.attempts');
    for ($i = 0; $i <= $limit; $i++) {
        $this->postJson('/api/remote/sftp/auth', ['type' => 'public_key', 'username' => getUsername(), 'password' => $certificate])->assertStatus($i === $limit ? 429 : 403);
    }
});
test('request is rejected if server belongs to different node', function (string $type) {
    $node2 = $this->createServerModel()->node;
    setAuthorization($node2);
    Hash::spy();
    $password = $type === 'public_key' ? UserSSHKey::factory()->for($this->user)->create()->public_key : 'foobar';
    $this->postJson('/api/remote/sftp/auth', ['type' => $type, 'username' => getUsername(), 'password' => $password])->assertForbidden();
    Hash::shouldNotHaveReceived('check');
    Hash::shouldNotHaveReceived('make');
    expect(failedSftpLogins($this->user)->count())->toBe(0);
})->with('authorizationTypeDataProvider');
test('wrong-node requests never lock the user out', function () {
    $node2 = $this->createServerModel()->node;
    setAuthorization($node2);
    $limit = (int) config('auth.lockout.attempts');
    for ($i = 0; $i <= $limit + 2; $i++) {
        $this->postJson('/api/remote/sftp/auth', ['username' => getUsername(), 'password' => 'wrong password'])->assertForbidden();
    }
    setAuthorization();
    $this->postJson('/api/remote/sftp/auth', ['username' => getUsername(), 'password' => 'foobar'])->assertOk();
});
test('request is denied if user lacks sftp permission', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::FileRead->value]);
    $user->update(['password' => 'foobar']);
    setAuthorization($server->node);
    $this->postJson('/api/remote/sftp/auth', ['username' => $user->username.'.'.$server->uuidShort, 'password' => 'foobar'])->assertForbidden()->assertJsonPath('errors.0.detail', 'You do not have permission to access SFTP for this server.');
});
test('invalid server state returns conflict error', function (string $status) {
    $this->server->update(['status' => $status]);
    $this->postJson('/api/remote/sftp/auth', ['username' => getUsername(), 'password' => 'foobar'])->assertStatus(409);
})->with('serverStateDataProvider');
test('user permissions are returned correctly', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::FileRead->value, Permissions::FileSftp->value]);
    $user->update(['password' => 'foobar']);
    setAuthorization($server->node);
    $data = ['username' => $user->username.'.'.$server->uuidShort, 'password' => 'foobar'];
    $this->postJson('/api/remote/sftp/auth', $data)->assertOk()->assertJsonPath('permissions', [Permissions::FileRead->value, Permissions::FileSftp->value]);
    $user->update(['root_admin' => true]);
    $this->postJson('/api/remote/sftp/auth', $data)->assertOk()->assertJsonPath('permissions.0', '*');
    setAuthorization();
    $data['username'] = $user->username.'.'.$this->server->uuidShort;
    $this->post('/api/remote/sftp/auth', $data)->assertOk()->assertJsonPath('permissions.0', '*');
    $user->update(['root_admin' => false]);
    $this->post('/api/remote/sftp/auth', $data)->assertForbidden();
});
/**
 * Returns the username for connecting to SFTP.
 */
function getUsername(bool $long = false): string
{
    return (function () use ($long) {
        return $this->user->username.'.'.($long ? $this->server->uuid : $this->server->uuidShort);
    })->call(pterodactylTestCase());
}
/**
 * Sets the authorization header for the rest of the test.
 */
function setAuthorization(?Node $node = null): void
{
    (function () use ($node) {
        $node = $node ?? $this->server->node;
        $this->withHeader('Authorization', 'Bearer '.$node->daemon_token_id.'.'.decrypt($node->daemon_token));
    })->call(pterodactylTestCase());
}
/**
 * Failed SFTP password login activity logged against a user.
 *
 * @return Builder<ActivityLog>
 */
function failedSftpLogins(User $user): Builder
{
    return ActivityLog::query()
        ->where('event', 'auth:sftp.fail')
        ->whereHas('subjects', fn (Builder $query) => $query->where('subject_id', $user->id)->where('subject_type', $user->getMorphClass()));
}
