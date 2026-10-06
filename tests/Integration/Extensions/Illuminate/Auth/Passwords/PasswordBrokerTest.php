<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Extensions\Illuminate\Auth\Passwords\PasswordBrokerTest;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Pterodactyl\Extensions\Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Pterodactyl\Extensions\Illuminate\Auth\Passwords\PasswordBroker;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use RuntimeException;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

function countingHasher(): Hasher
{
    return new class implements Hasher
    {
        public int $calls = 0;

        public function info($hashedValue): array
        {
            return password_get_info($hashedValue);
        }

        public function make($value, array $options = []): string
        {
            $this->calls++;

            return password_hash($value, PASSWORD_BCRYPT, ['cost' => 4]);
        }

        public function check($value, $hashedValue, array $options = []): bool
        {
            $this->calls++;

            return password_verify($value, $hashedValue);
        }

        public function needsRehash($hashedValue, array $options = []): bool
        {
            return false;
        }
    };
}

beforeEach(function () {
    $this->hasher = countingHasher();
    $this->tokens = new DatabaseTokenRepository(DB::connection(), $this->hasher, 'password_resets', 'key', 3600, 60);
    $this->broker = new PasswordBroker($this->tokens, Auth::createUserProvider('users'), timeboxDuration: 0);
});
test('container resolves the timing safe password broker', function () {
    expect(Password::broker())->toBeInstanceOf(PasswordBroker::class);
});
test('token check hashes once whether or not a valid record exists', function () {
    $user = User::factory()->create();

    expect($this->tokens->exists($user, 'junk'))->toBeFalse()->and($this->hasher->calls)->toBe(1);

    $token = $this->tokens->create($user);

    $this->hasher->calls = 0;
    expect($this->tokens->exists($user, 'junk'))->toBeFalse()->and($this->hasher->calls)->toBe(1);

    $this->hasher->calls = 0;
    expect($this->tokens->exists($user, $token))->toBeTrue()->and($this->hasher->calls)->toBe(1);

    $this->travel(61)->minutes();
    $this->hasher->calls = 0;
    expect($this->tokens->exists($user, $token))->toBeFalse()->and($this->hasher->calls)->toBe(1);
});
test('failed resets hash once for unknown and known emails', function () {
    $user = User::factory()->create();
    $credentials = fn (string $email) => ['email' => $email, 'token' => 'junk', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
    $reject = fn () => throw new RuntimeException('Password should not be reset.');

    expect($this->broker->reset($credentials('unknown@example.com'), $reject))->toBe(Password::INVALID_USER)
        ->and($this->hasher->calls)->toBe(1);

    $this->hasher->calls = 0;
    expect($this->broker->reset($credentials($user->email), $reject))->toBe(Password::INVALID_TOKEN)
        ->and($this->hasher->calls)->toBe(1);
});
test('reset link requests hash once for unknown, new and throttled emails', function () {
    $user = User::factory()->create();
    $sent = fn () => Password::RESET_LINK_SENT;
    Hash::swap($this->hasher);

    expect($this->broker->sendResetLink(['email' => 'unknown@example.com'], $sent))->toBe(Password::INVALID_USER)
        ->and($this->hasher->calls)->toBe(1);

    $this->hasher->calls = 0;
    expect($this->broker->sendResetLink(['email' => $user->email], $sent))->toBe(Password::RESET_LINK_SENT)
        ->and($this->hasher->calls)->toBe(1);

    $this->hasher->calls = 0;
    expect($this->broker->sendResetLink(['email' => $user->email], $sent))->toBe(Password::RESET_THROTTLED)
        ->and($this->hasher->calls)->toBe(1);
});
