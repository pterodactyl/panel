<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\ApiKeys\ApiKeyControllerTest;

use Illuminate\Http\Response;
use Illuminate\Testing\Assert;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root-administrator. */
dataset('apiKeyEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.api-keys'], ['getJson', 'api.admin.api-keys.view'], ['postJson', 'api.admin.api-keys.store'], ['putJson', 'api.admin.api-keys.update'], ['delete', 'api.admin.api-keys.delete']]);
dataset('apiKeyListSortsDataProvider', fn (): array => [['memo'], ['-memo'], ['created_at'], ['-created_at']]);
test('get api keys', function (): void {
    $keys = ApiKey::factory()->times(2)->create(['user_id' => $this->getAdminUser()->id, 'key_type' => ApiKey::TYPE_APPLICATION]);
    $response = $this->getJson(route('api.admin.api-keys', ['per_page' => 60]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['identifier', 'memo', 'created_by' => ['id', 'username', 'email'], 'last_used_at', 'created_at', 'r_servers']], ['object', 'attributes' => ['identifier', 'memo', 'created_by' => ['id', 'username', 'email'], 'last_used_at', 'created_at', 'r_servers']]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJsonPath('data.0.attributes.created_by.id', $this->getAdminUser()->id);
    Assert::assertArraySubset(['object' => 'api_key', 'attributes' => ['identifier' => $keys[0]->identifier, 'memo' => $keys[0]->memo, 'created_by' => ['id' => $this->getAdminUser()->id, 'username' => $this->getAdminUser()->username, 'email' => $this->getAdminUser()->email], 'last_used_at' => null, 'created_at' => $keys[0]->created_at->toAtomString(), 'relationships' => [], 'r_servers' => 0, 'r_nodes' => 0, 'r_allocations' => 0, 'r_users' => 0, 'r_locations' => 0, 'r_eggs' => 0, 'r_database_hosts' => 0, 'r_server_databases' => 0]], collect($response->json('data'))->firstWhere('attributes.identifier', $keys[0]->identifier), true);
    Assert::assertArraySubset(['object' => 'api_key', 'attributes' => ['identifier' => $keys[1]->identifier, 'memo' => $keys[1]->memo, 'created_by' => ['id' => $this->getAdminUser()->id, 'username' => $this->getAdminUser()->username, 'email' => $this->getAdminUser()->email], 'last_used_at' => null, 'created_at' => $keys[1]->created_at->toAtomString(), 'relationships' => [], 'r_servers' => 0, 'r_nodes' => 0, 'r_allocations' => 0, 'r_users' => 0, 'r_locations' => 0, 'r_eggs' => 0, 'r_database_hosts' => 0, 'r_server_databases' => 0]], collect($response->json('data'))->firstWhere('attributes.identifier', $keys[1]->identifier), true);
});
test('get api keys accepts data table sorts', function (string $sort): void {
    ApiKey::factory()->create(['user_id' => $this->getAdminUser()->id, 'key_type' => ApiKey::TYPE_APPLICATION, 'memo' => 'Data table sort A']);
    ApiKey::factory()->create(['user_id' => $this->getAdminUser()->id, 'key_type' => ApiKey::TYPE_APPLICATION, 'memo' => 'Data table sort Z']);
    $response = $this->getJson(route('api.admin.api-keys', ['filter' => ['memo' => 'Data table sort'], 'sort' => $sort, 'per_page' => 100]));
    $response->assertOk();
    $response->assertJsonCount(2, 'data');
})->with('apiKeyListSortsDataProvider');
test('api key listing never exposes token', function (): void {
    ApiKey::factory()->create(['user_id' => $this->getAdminUser()->id, 'key_type' => ApiKey::TYPE_APPLICATION]);
    $response = $this->getJson(route('api.admin.api-keys'));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonMissingPath('data.0.attributes.token');
    $response->assertJsonMissingPath('data.0.attributes.secret_token');
});
test('create api key', function (): void {
    $response = $this->postJson(route('api.admin.api-keys.store'), ['memo' => 'Test application key.', 'r_'.AdminAcl::RESOURCE_SERVERS => AdminAcl::READ, 'r_'.AdminAcl::RESOURCE_USERS => AdminAcl::READ | AdminAcl::WRITE]);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonStructure(['object', 'attributes' => ['identifier', 'memo', 'created_by' => ['id', 'username', 'email'], 'last_used_at', 'created_at', 'r_servers', 'r_users'], 'meta' => ['secret_token']]);
    $response->assertJsonPath('attributes.created_by.id', $this->getAdminUser()->id);
    $this->assertDatabaseHas('api_keys', ['memo' => 'Test application key.', 'key_type' => ApiKey::TYPE_APPLICATION, 'user_id' => $this->getAdminUser()->id, 'r_'.AdminAcl::RESOURCE_SERVERS => AdminAcl::READ, 'r_'.AdminAcl::RESOURCE_USERS => AdminAcl::READ | AdminAcl::WRITE]);
    $key = ApiKey::query()->where('memo', 'Test application key.')->firstOrFail();
    // Secret is identifier + decrypted token, returned once on creation and never stored in plaintext.
    $secret = $response->json('meta.secret_token');
    expect($secret)->toBe($key->identifier.decrypt($key->token));
    expect($secret)->toStartWith($key->identifier);

    $response->assertJsonMissingPath('attributes.token');
    $response->assertJson(['object' => 'api_key', 'attributes' => ['identifier' => $key->identifier, 'memo' => 'Test application key.', 'created_by' => ['id' => $this->getAdminUser()->id, 'username' => $this->getAdminUser()->username, 'email' => $this->getAdminUser()->email], 'last_used_at' => null, 'created_at' => $key->created_at->toAtomString(), 'relationships' => [], 'r_servers' => 1, 'r_nodes' => 0, 'r_allocations' => 0, 'r_users' => 3, 'r_locations' => 0, 'r_eggs' => 0, 'r_database_hosts' => 0, 'r_server_databases' => 0]]);
});
test('view api key', function (): void {
    $key = ApiKey::factory()->create(['user_id' => $this->getAdminUser()->id, 'key_type' => ApiKey::TYPE_APPLICATION]);
    $response = $this->getJson(route('api.admin.api-keys.view', ['identifier' => $key->identifier]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonMissingPath('attributes.token');
    $response->assertJsonPath('attributes.created_by.id', $this->getAdminUser()->id);
    $response->assertJson(['object' => 'api_key', 'attributes' => ['identifier' => $key->identifier, 'memo' => $key->memo, 'created_by' => ['id' => $this->getAdminUser()->id, 'username' => $this->getAdminUser()->username, 'email' => $this->getAdminUser()->email], 'last_used_at' => null, 'created_at' => $key->created_at->toAtomString(), 'relationships' => [], 'r_servers' => 0, 'r_nodes' => 0, 'r_allocations' => 0, 'r_users' => 0, 'r_locations' => 0, 'r_eggs' => 0, 'r_database_hosts' => 0, 'r_server_databases' => 0]]);
});
test('view missing api key', function (): void {
    $response = $this->getJson(route('api.admin.api-keys.view', ['identifier' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('update api key', function (): void {
    $key = ApiKey::factory()->create(['user_id' => $this->getAdminUser()->id, 'key_type' => ApiKey::TYPE_APPLICATION, 'memo' => 'Original memo.', 'r_'.AdminAcl::RESOURCE_SERVERS => AdminAcl::READ]);
    $identifier = $key->identifier;
    $token = $key->token;
    $response = $this->putJson(route('api.admin.api-keys.update', ['identifier' => $key->identifier]), ['memo' => 'Updated memo.', 'r_'.AdminAcl::RESOURCE_SERVERS => AdminAcl::READ | AdminAcl::WRITE, 'r_'.AdminAcl::RESOURCE_USERS => AdminAcl::READ]);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('attributes.memo', 'Updated memo.');
    $response->assertJsonPath('attributes.created_by.id', $this->getAdminUser()->id);
    $this->assertDatabaseHas('api_keys', ['id' => $key->id, 'identifier' => $key->identifier, 'memo' => 'Updated memo.', 'r_'.AdminAcl::RESOURCE_SERVERS => AdminAcl::READ | AdminAcl::WRITE, 'r_'.AdminAcl::RESOURCE_USERS => AdminAcl::READ]);
    // The identifier and token are never regenerated by an update.
    expect($key->refresh()->identifier)->toBe($identifier)
        ->and($key->token)->toBe($token);
});
test('delete api key', function (): void {
    $key = ApiKey::factory()->create(['user_id' => $this->getAdminUser()->id, 'key_type' => ApiKey::TYPE_APPLICATION]);
    $this->assertDatabaseHas('api_keys', ['id' => $key->id]);
    $response = $this->delete(route('api.admin.api-keys.delete', ['identifier' => $key->identifier]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('api_keys', ['id' => $key->id]);
});
test('delete missing api key', function (): void {
    $response = $this->delete(route('api.admin.api-keys.delete', ['identifier' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $key = ApiKey::factory()->create(['user_id' => $this->getAdminUser()->id, 'key_type' => ApiKey::TYPE_APPLICATION]);
    $response = $this->{$method}(route($routeName, ['identifier' => $key->identifier]));
    $this->assertAccessDeniedJson($response);
})->with('apiKeyEndpointsDataProvider');
test('invalid payloads return validation errors', function (): void {
    $response = $this->postJson(route('api.admin.api-keys.store'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    $error = $errors->firstWhere('meta.source_field', 'memo');
    expect($error)->not->toBeNull('Expected a validation error for the [memo] field.');
    expect($error['meta']['rule'])->toBe('required');
    expect($error['detail'])->not->toBeEmpty();
});
