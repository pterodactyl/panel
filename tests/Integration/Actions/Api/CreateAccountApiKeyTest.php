<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Api\CreateAccountApiKeyTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Api\CreatesAccountApiKeys;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('account api keys created without the http api meet the same rules', function (?string $description, ?array $allowedIps, array $fields): void {
    $user = User::factory()->create();

    try {
        $this->app->make(CreatesAccountApiKeys::class)->create($user, $description, $allowedIps);
        $this->fail('The api key was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe($fields);
    }

    expect($user->apiKeys()->exists())->toBeFalse();
})->with([
    'a null description' => [null, null, ['description']],
    'a blank description' => ['', null, ['description']],
    'a description that is too long' => [str_repeat('a', 501), null, ['description']],
    'allowed ips that are not a list' => ['Direct', ['office' => '127.0.0.1'], ['allowed_ips']],
    'more than fifty allowed ips' => ['Direct', array_map(fn (int $i): string => '10.0.0.'.$i, range(1, 51)), ['allowed_ips']],
    'allowed ips that do not parse' => ['Direct', ['hodor', '127.0.0.1', 'hodor/24'], ['allowed_ips.0', 'allowed_ips.2']],
    'an allowed ip that is not a string' => ['Direct', ['127.0.0.1', 5], ['allowed_ips.1']],
]);

test('a key keeps its description and allowed ips, and one given none can be used from anywhere', function (): void {
    $user = User::factory()->create();
    $keys = $this->app->make(CreatesAccountApiKeys::class);

    $restricted = $keys->create($user, 'Restricted', ['127.0.0.1', '192.168.10.10/28', '::1'])->accessToken->refresh();
    expect($restricted->memo)->toBe('Restricted')
        ->and($restricted->key_type)->toBe(ApiKey::TYPE_ACCOUNT)
        ->and($restricted->allowed_ips)->toBe(['127.0.0.1', '192.168.10.10/28', '::1']);

    expect($keys->create($user, 'Open', null)->accessToken->refresh()->allowed_ips)->toBe([])
        ->and($keys->create($user, 'Empty', [])->accessToken->refresh()->allowed_ips)->toBe([]);
});

test('an account holds at most 25 keys', function (): void {
    $user = User::factory()->create();
    ApiKey::factory()->times(25)->for($user)->create(['key_type' => ApiKey::TYPE_ACCOUNT]);

    expect(fn () => $this->app->make(CreatesAccountApiKeys::class)->create($user, 'One too many', null))
        ->toThrow(DisplayException::class, 'You have reached the account limit for number of API keys.');
    expect($user->apiKeys()->count())->toBe(25);
});
