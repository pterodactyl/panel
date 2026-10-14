<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Api\CreateApiKeyTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Api\CreatesApiKeys;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, array<array-key, int|string>|int|string|null> */
function apiKey(User $user, array $overrides = []): array
{
    return [...['memo' => 'Direct', 'user_id' => $user->id], ...$overrides];
}

test('api keys created without the http api meet the same rules', function (array $input, array $permissions, string $field): void {
    $user = User::factory()->create();
    $count = ApiKey::query()->count();

    try {
        $this->app->make(CreatesApiKeys::class)->create(ApiKey::TYPE_APPLICATION, apiKey($user, $input), $permissions);
        $this->fail('The api key was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect(ApiKey::query()->count())->toBe($count);
})->with([
    'a missing memo' => [['memo' => null], [], 'memo'],
    'a memo that is too long' => [['memo' => str_repeat('a', 501)], [], 'memo'],
    'a missing user' => [['user_id' => null], [], 'user_id'],
    'an unknown user' => [['user_id' => 0], [], 'user_id'],
    'a user that is not a number' => [['user_id' => 'admin'], [], 'user_id'],
    'allowed ips that are not a list' => [['allowed_ips' => ['office' => '127.0.0.1']], [], 'allowed_ips'],
    'more than fifty allowed ips' => [['allowed_ips' => array_map(fn (int $i): string => '10.0.0.'.$i, range(1, 51))], [], 'allowed_ips'],
    'an allowed ip that does not parse' => [['allowed_ips' => ['127.0.0.1', 'hodor']], [], 'allowed_ips.1'],
    'an allowed ip that is not a string' => [['allowed_ips' => [127]], [], 'allowed_ips.0'],
    'a permission above read and write' => [[], ['r_servers' => 4], 'r_servers'],
    'a negative permission' => [[], ['r_nodes' => -1], 'r_nodes'],
    'a permission that is not a number' => [[], ['r_users' => 'write'], 'r_users'],
    'a null permission' => [[], ['r_eggs' => null], 'r_eggs'],
]);

test('values are coerced the way laravel validates them', function (): void {
    $user = User::factory()->create();

    $key = $this->app->make(CreatesApiKeys::class)->create(ApiKey::TYPE_APPLICATION, apiKey($user, ['user_id' => (string) $user->id, 'memo' => '1024']), [
        'r_servers' => (string) (AdminAcl::READ | AdminAcl::WRITE),
        'r_nodes' => '1',
        'r_users' => AdminAcl::WRITE,
    ]);

    expect($key->user_id)->toBe($user->id)
        ->and($key->memo)->toBe('1024')
        ->and($key->r_servers)->toBe(AdminAcl::READ | AdminAcl::WRITE)
        ->and($key->r_nodes)->toBe(AdminAcl::READ)
        ->and($key->r_users)->toBe(AdminAcl::WRITE)
        ->and($key->r_eggs)->toBe(AdminAcl::NONE);
});

test('a key keeps the allowed ips it is given, and one given none or null has none', function (): void {
    $user = User::factory()->create();
    $keys = $this->app->make(CreatesApiKeys::class);

    expect($keys->create(ApiKey::TYPE_APPLICATION, apiKey($user, ['allowed_ips' => ['127.0.0.1', '10.0.0.0/8', '::1']]))->allowed_ips)->toBe(['127.0.0.1', '10.0.0.0/8', '::1'])
        ->and($keys->create(ApiKey::TYPE_APPLICATION, apiKey($user, ['allowed_ips' => []]))->allowed_ips)->toBe([])
        ->and($keys->create(ApiKey::TYPE_APPLICATION, apiKey($user, ['allowed_ips' => null]))->allowed_ips)->toBeNull()
        ->and($keys->create(ApiKey::TYPE_APPLICATION, apiKey($user))->allowed_ips)->toBeNull();
});

test('only application keys get resource permissions', function (): void {
    $user = User::factory()->create();

    $key = $this->app->make(CreatesApiKeys::class)->create(ApiKey::TYPE_ACCOUNT, apiKey($user), ['r_servers' => AdminAcl::READ]);

    expect($key->key_type)->toBe(ApiKey::TYPE_ACCOUNT)
        ->and($key->identifier)->toStartWith('ptlc_')
        ->and($key->r_servers)->toBe(AdminAcl::NONE);
});
