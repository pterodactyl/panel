<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Users\UserControllerTest;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

uses(ApplicationApiIntegrationTestCase::class);
/**
 * Endpoints that should return a 403 error when the key does not have write
 * permissions for user management.
 */
dataset('userWriteEndpointsDataProvider', fn (): array => [['postJson', '/api/application/users'], ['patchJson', '/api/application/users/{id}'], ['delete', '/api/application/users/{id}']]);
test('get users', function (): void {
    $user = User::factory()->create();
    $response = $this->getJson('/api/application/users?per_page=60');
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at']], ['object', 'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at']]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJson(['object' => 'list', 'data' => [[], []], 'meta' => ['pagination' => ['total' => 2, 'count' => 2, 'per_page' => 60, 'current_page' => 1, 'total_pages' => 1]]])->assertJsonFragment(['object' => 'user', 'attributes' => ['id' => $this->getApiUser()->id, 'external_id' => $this->getApiUser()->external_id, 'uuid' => $this->getApiUser()->uuid, 'username' => $this->getApiUser()->username, 'email' => $this->getApiUser()->email, 'first_name' => $this->getApiUser()->name_first, 'last_name' => $this->getApiUser()->name_last, 'language' => $this->getApiUser()->language, 'root_admin' => $this->getApiUser()->root_admin, '2fa' => (bool) $this->getApiUser()->totp_enabled, 'relationships' => [], 'created_at' => $this->formatTimestamp($this->getApiUser()->created_at), 'updated_at' => $this->formatTimestamp($this->getApiUser()->updated_at)]])->assertJsonFragment(['object' => 'user', 'attributes' => ['id' => $user->id, 'external_id' => $user->external_id, 'uuid' => $user->uuid, 'username' => $user->username, 'email' => $user->email, 'first_name' => $user->name_first, 'last_name' => $user->name_last, 'language' => $user->language, 'root_admin' => (bool) $user->root_admin, '2fa' => (bool) $user->totp_enabled, 'relationships' => [], 'created_at' => $this->formatTimestamp($user->created_at), 'updated_at' => $this->formatTimestamp($user->updated_at)]]);
});
test('get single user', function (): void {
    $user = User::factory()->create();
    $response = $this->getJson('/api/application/users/'.$user->id);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at']]);
    $response->assertJson(['object' => 'user', 'attributes' => ['id' => $user->id, 'external_id' => $user->external_id, 'uuid' => $user->uuid, 'username' => $user->username, 'email' => $user->email, 'first_name' => $user->name_first, 'last_name' => $user->name_last, 'language' => $user->language, 'root_admin' => (bool) $user->root_admin, '2fa' => (bool) $user->totp_enabled, 'created_at' => $this->formatTimestamp($user->created_at), 'updated_at' => $this->formatTimestamp($user->updated_at)]]);
});
test('relationships can be loaded', function (): void {
    $user = User::factory()->create();
    $server = $this->createServerModel(['user_id' => $user->id]);
    $response = $this->getJson('/api/application/users/'.$user->id.'?include=servers');
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at', 'relationships' => ['servers' => ['object', 'data' => [['object', 'attributes' => []]]]]]]);
    $response->assertJsonCount(1, 'attributes.relationships.servers.data');
    $response->assertJson(['attributes' => ['relationships' => ['servers' => ['object' => 'list', 'data' => [['object' => 'server', 'attributes' => ['id' => $server->id, 'uuid' => $server->uuid, 'identifier' => $server->uuidShort, 'name' => $server->name, 'user' => $user->id, 'node' => $server->node_id]]]]]]]);
});
test('key without permission cannot load relationship', function (): void {
    $this->createNewDefaultApiKey($this->getApiUser(), ['r_servers' => 0]);
    $user = User::factory()->create();
    $this->createServerModel(['user_id' => $user->id]);
    $response = $this->getJson('/api/application/users/'.$user->id.'?include=servers');
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2)->assertJsonCount(1, 'attributes.relationships');
    $response->assertJsonStructure(['attributes' => ['relationships' => ['servers' => ['object', 'attributes']]]]);
    // Just assert that we see the expected relationship IDs in the response.
    $response->assertJson(['attributes' => ['relationships' => ['servers' => ['object' => 'null_resource', 'attributes' => null]]]]);
});
test('get missing user', function (): void {
    $response = $this->getJson('/api/application/users/nil');
    $this->assertNotFoundJson($response);
});
test('error returned if no permission', function (): void {
    $user = User::factory()->create();
    $this->createNewDefaultApiKey($this->getApiUser(), ['r_users' => 0]);
    $response = $this->getJson('/api/application/users/'.$user->id);
    $this->assertAccessDeniedJson($response);
});
test('create user', function (): void {
    $response = $this->postJson('/api/application/users', ['username' => 'testuser', 'email' => 'test@example.com', 'first_name' => 'Test', 'last_name' => 'User']);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonCount(3);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at'], 'meta' => ['resource']]);
    $this->assertDatabaseHas('users', ['username' => 'testuser', 'email' => 'test@example.com']);
    $user = User::query()->where('username', 'testuser')->first();
    $response->assertJson(['object' => 'user', 'attributes' => ['id' => $user->id, 'uuid' => $user->uuid, 'external_id' => $user->external_id, 'username' => $user->username, 'email' => $user->email, 'first_name' => $user->name_first, 'last_name' => $user->name_last, 'language' => $user->language, 'root_admin' => (bool) $user->root_admin, '2fa' => (bool) $user->use_totp], 'meta' => ['resource' => route('api.application.users.view', $user->id)]], true);

    $log = $user->activity()->where('event', 'user:user.create')->sole();
    expect($log->actor_id)->toBe($this->getApiUser()->id)
        ->and($log->properties->only(['email', 'username', 'admin'])->all())->toBe(['email' => 'test@example.com', 'username' => 'testuser', 'admin' => false]);
});
test('update user', function (): void {
    $user = User::factory()->create();
    $response = $this->patchJson('/api/application/users/'.$user->id, ['username' => 'new.test.name', 'email' => 'new@emailtest.com', 'first_name' => $user->name_first, 'last_name' => $user->name_last]);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at']]);
    $this->assertDatabaseHas('users', ['username' => 'new.test.name', 'email' => 'new@emailtest.com']);
    $user = $user->fresh();
    $response->assertJson(['object' => 'user', 'attributes' => ['id' => $user->id, 'uuid' => $user->uuid, 'external_id' => $user->external_id, 'username' => $user->username, 'email' => $user->email, 'first_name' => $user->name_first, 'last_name' => $user->name_last, 'language' => $user->language, 'root_admin' => (bool) $user->root_admin, '2fa' => (bool) $user->use_totp]]);
});
test('passwords shorter than eight characters are rejected', function (string $method, string $suffix): void {
    $user = User::factory()->create();
    $url = '/api/application/users'.str_replace('{user}', (string) $user->id, $suffix);
    $response = $this->{$method}($url, ['username' => 'weakuser', 'email' => 'weak@example.com', 'first_name' => 'Weak', 'last_name' => 'Password', 'password' => 'abc']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonPath('errors.0.meta.source_field', 'password')->assertJsonPath('errors.0.meta.rule', 'min');
    $this->assertDatabaseMissing('users', ['username' => 'weakuser']);
})->with([['postJson', ''], ['patchJson', '/{user}']]);
test('a user can be created with an eight character password', function (): void {
    $this->postJson('/api/application/users', ['username' => 'okuser', 'email' => 'ok@example.com', 'first_name' => 'Ok', 'last_name' => 'Password', 'password' => 'abcdefgh'])->assertCreated();
    expect(Hash::check('abcdefgh', User::query()->where('username', 'okuser')->firstOrFail()->password))->toBeTrue();
});
test('a user email must not start with a dash', function (string $method, string $suffix): void {
    $user = User::factory()->create();
    $this->{$method}('/api/application/users'.str_replace('{user}', (string) $user->id, $suffix), ['username' => 'dashuser', 'email' => '-x@example.com', 'first_name' => 'Dash', 'last_name' => 'User'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'email')
        ->assertJsonPath('errors.0.meta.rule', 'p_user_email');
    $this->assertDatabaseMissing('users', ['email' => '-x@example.com']);
})->with([['postJson', ''], ['patchJson', '/{user}']]);
test('delete user', function (): void {
    $user = User::factory()->create();
    $this->assertDatabaseHas('users', ['id' => $user->id]);
    $response = $this->delete('/api/application/users/'.$user->id);
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
test('api key without write permissions', function (string $method, string $url): void {
    $this->createNewDefaultApiKey($this->getApiUser(), ['r_users' => AdminAcl::READ]);
    if (str_contains($url, '{id}')) {
        $user = User::factory()->create();
        $url = str_replace('{id}', (string) $user->id, $url);
    }

    $response = $this->{$method}($url);
    $this->assertAccessDeniedJson($response);
})->with('userWriteEndpointsDataProvider');
