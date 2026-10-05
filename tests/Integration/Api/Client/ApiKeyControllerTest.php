<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\ApiKeyControllerTest;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\ActivityLogged;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
/**
 * Cleanup after tests.
 */
afterEach(function (): void {
    ApiKey::query()->forceDelete();
});
/**
 * Provides some different IP address combinations that can be used when
 * testing that we accept the expected IP values.
 */
dataset('validIPAddressDataProvider', fn (): array => [[[]], [['127.0.0.1']], [['127.0.0.1', '::1']], [['::ffff:7f00:1']], [['127.0.0.1', '192.168.1.100', '192.168.10.10/28']], [['127.0.0.1/32', '192.168.100.100/27', '::1', '::1/128']]]);
test('api keys are returned', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var ApiKey $key */
    $key = ApiKey::factory()->for($user)->create(['key_type' => ApiKey::TYPE_ACCOUNT]);
    $response = $this->actingAs($user)->get('/api/client/account/api-keys')->assertOk()->assertJsonPath('object', 'list')->assertJsonPath('data.0.object', ApiKey::RESOURCE_NAME);
    expect($response->json('data.0.attributes'))->toBe([
        'identifier' => $key->identifier,
        'description' => $key->memo,
        'allowed_ips' => null,
        'last_used_at' => null,
        'created_at' => $key->created_at->toAtomString(),
    ]);
    $response->assertJsonMissingPath('data.0.attributes.token');
    $response->assertJsonMissingPath('data.0.attributes.secret_token');
});
test('api key can be created for account', function (array $data): void {
    /** @var User $user */
    $user = User::factory()->create();
    // Small subtest to ensure we're always comparing the  number of keys to the
    // specific logged in account, and not just the total number of keys stored in
    // the database.
    ApiKey::factory()->times(10)->create(['user_id' => User::factory()->create()->id, 'key_type' => ApiKey::TYPE_ACCOUNT]);
    $response = $this->actingAs($user)->postJson('/api/client/account/api-keys', ['description' => 'Test Description', 'allowed_ips' => $data])->assertOk()->assertJsonPath('object', ApiKey::RESOURCE_NAME);
    /** @var ApiKey $key */
    $key = ApiKey::query()->where('identifier', $response->json('attributes.identifier'))->firstOrFail();
    expect($response->json('attributes'))->toBe([
        'identifier' => $key->identifier,
        'description' => 'Test Description',
        'allowed_ips' => $data,
        'last_used_at' => null,
        'created_at' => $key->created_at->toAtomString(),
    ]);
    $response->assertJsonMissingPath('attributes.token');
    $this->assertDatabaseHas('api_keys', ['id' => $key->id, 'memo' => 'Test Description', 'user_id' => $user->id]);
    $response->assertJsonPath('meta.secret_token', decrypt($key->token));
    $this->assertActivityFor('user:api-key.create', $user, [$key, $user]);
})->with('validIPAddressDataProvider');
test('api key cannot specify more than fifty ips', function (): void {
    $ips = [];
    for ($i = 0; $i < 100; $i++) {
        $ips[] = '127.0.0.'.$i;
    }

    $this->actingAs(User::factory()->create())->postJson('/api/client/account/api-keys', ['description' => 'Test Data', 'allowed_ips' => $ips])->assertUnprocessable()->assertJsonPath('errors.0.detail', 'The allowed ips may not have more than 50 items.');
});
test('api key allowed ips must be a list', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/client/account/api-keys', ['description' => 'Associative IPs', 'allowed_ips' => ['primary' => '127.0.0.1']])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'allowed_ips');

    $this->assertDatabaseMissing('api_keys', ['user_id' => $user->id]);
});
test('api key limit is applied', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    ApiKey::factory()->times(25)->for($user)->create(['key_type' => ApiKey::TYPE_ACCOUNT]);
    $this->actingAs($user)->postJson('/api/client/account/api-keys', ['description' => 'Test Description', 'allowed_ips' => ['127.0.0.1']])->assertStatus(Response::HTTP_BAD_REQUEST)->assertJsonPath('errors.0.code', 'DisplayException')->assertJsonPath('errors.0.detail', 'You have reached the account limit for number of API keys.');
});
test('validation error is returned for bad requests', function (): void {
    $this->actingAs(User::factory()->create());
    $this->postJson('/api/client/account/api-keys', ['description' => '', 'allowed_ips' => ['127.0.0.1']])->assertUnprocessable()->assertJsonPath('errors.0.meta.rule', 'required')->assertJsonPath('errors.0.detail', 'The description field is required.');
    $this->postJson('/api/client/account/api-keys', ['description' => str_repeat('a', 501), 'allowed_ips' => ['127.0.0.1']])->assertUnprocessable()->assertJsonPath('errors.0.meta.rule', 'max')->assertJsonPath('errors.0.detail', 'The description may not be greater than 500 characters.');
    $this->postJson('/api/client/account/api-keys', ['description' => 'Foobar', 'allowed_ips' => ['hodor', '127.0.0.1', 'hodor/24']])->assertUnprocessable()->assertJsonPath('errors.0.detail', '"hodor" is not a valid IP address or CIDR range.')->assertJsonPath('errors.0.meta.source_field', 'allowed_ips.0')->assertJsonPath('errors.1.detail', '"hodor/24" is not a valid IP address or CIDR range.')->assertJsonPath('errors.1.meta.source_field', 'allowed_ips.2');
});
test('api key can be deleted', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var ApiKey $key */
    $key = ApiKey::factory()->for($user)->create(['key_type' => ApiKey::TYPE_ACCOUNT]);
    $response = $this->actingAs($user)->delete('/api/client/account/api-keys/'.$key->identifier);
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('api_keys', ['id' => $key->id]);
    $this->assertActivityFor('user:api-key.delete', $user, $user);
});
test('non existent api key deletion returns404 error', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var ApiKey $key */
    $key = ApiKey::factory()->create(['user_id' => $user->id, 'key_type' => ApiKey::TYPE_ACCOUNT]);
    $response = $this->actingAs($user)->delete('/api/client/account/api-keys/1234');
    $response->assertNotFound();
    $this->assertDatabaseHas('api_keys', ['id' => $key->id]);
    Event::assertNotDispatched(ActivityLogged::class);
});
test('api key belonging to another user cannot be deleted', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var User $user2 */
    $user2 = User::factory()->create();
    /** @var ApiKey $key */
    $key = ApiKey::factory()->for($user2)->create(['key_type' => ApiKey::TYPE_ACCOUNT]);
    $this->actingAs($user)->deleteJson('/api/client/account/api-keys/'.$key->identifier)->assertNotFound();
    $this->assertDatabaseHas('api_keys', ['id' => $key->id]);
    Event::assertNotDispatched(ActivityLogged::class);
});
test('application api key cannot be deleted', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var ApiKey $key */
    $key = ApiKey::factory()->for($user)->create(['key_type' => ApiKey::TYPE_APPLICATION]);
    $this->actingAs($user)->deleteJson('/api/client/account/api-keys/'.$key->identifier)->assertNotFound();
    $this->assertDatabaseHas('api_keys', ['id' => $key->id]);
});
