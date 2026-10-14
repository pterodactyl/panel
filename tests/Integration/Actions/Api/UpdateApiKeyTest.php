<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Api\UpdateApiKeyTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Api\UpdatesApiKeys;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('an update changes only what it is given', function (): void {
    $key = ApiKey::factory()->create(['memo' => 'Kept', 'r_servers' => AdminAcl::READ, 'r_nodes' => AdminAcl::READ | AdminAcl::WRITE]);
    $original = $key->refresh()->only(['identifier', 'token', 'user_id', 'memo', 'r_nodes']);
    $keys = $this->app->make(UpdatesApiKeys::class);

    $keys->update($key, [], ['r_servers' => '3', 'r_users' => 1]);

    $key->refresh();
    expect($key->r_servers)->toBe(AdminAcl::READ | AdminAcl::WRITE)
        ->and($key->r_users)->toBe(AdminAcl::READ)
        ->and($key->only(array_keys($original)))->toBe($original);

    $keys->update($key, ['memo' => 'Renamed']);

    $key->refresh();
    expect($key->memo)->toBe('Renamed')
        ->and($key->r_servers)->toBe(AdminAcl::READ | AdminAcl::WRITE)
        ->and($key->r_nodes)->toBe(AdminAcl::READ | AdminAcl::WRITE);
});

test('an update never changes a key owner or allowed ips', function (): void {
    $key = ApiKey::factory()->withAllowedIps()->create();
    $userId = $key->user_id;

    $this->app->make(UpdatesApiKeys::class)->update($key, ['memo' => 'Renamed', 'user_id' => 0, 'allowed_ips' => ['10.0.0.1']]);

    $key->refresh();
    expect($key->memo)->toBe('Renamed')
        ->and($key->user_id)->toBe($userId)
        ->and($key->allowed_ips)->toBe(['127.0.0.1']);
});

test('only application keys get resource permissions', function (): void {
    $key = ApiKey::factory()->create(['key_type' => ApiKey::TYPE_ACCOUNT]);

    $this->app->make(UpdatesApiKeys::class)->update($key, ['memo' => 'Account'], ['r_servers' => AdminAcl::READ]);

    $key->refresh();
    expect($key->memo)->toBe('Account')
        ->and($key->r_servers)->toBe(AdminAcl::NONE);
});

test('api keys updated without the http api meet the same rules', function (array $input, array $permissions, string $field): void {
    $key = ApiKey::factory()->create()->refresh();
    $before = $key->getAttributes();

    try {
        $this->app->make(UpdatesApiKeys::class)->update($key, $input, $permissions);
        $this->fail('The api key was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($key->fresh()->getAttributes())->toBe($before);
})->with([
    'a null memo' => [['memo' => null], [], 'memo'],
    'a blank memo' => [['memo' => ''], [], 'memo'],
    'a memo that is too long' => [['memo' => str_repeat('a', 501)], [], 'memo'],
    'a permission above read and write' => [['memo' => 'Valid'], ['r_servers' => 4], 'r_servers'],
    'a permission that is not a number' => [[], ['r_database_hosts' => 'read'], 'r_database_hosts'],
]);
