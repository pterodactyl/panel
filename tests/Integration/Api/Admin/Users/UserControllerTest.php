<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Users\UserControllerTest;

use Illuminate\Http\Response;
use Illuminate\Testing\Assert;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
const ADMIN_USER_ATTRIBUTES = ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'image', 'servers_count', 'subuser_of_count', 'created_at', 'updated_at'];
/** Fields the shared admin user search should match. */
dataset('userSearchDataProvider', fn (): array => [['username'], ['email'], ['uuid']]);
dataset('userListSortsDataProvider', fn (): array => [['email'], ['-email'], ['username'], ['-username'], ['created_at'], ['-created_at']]);
/** Endpoints that should return a 403 error when accessed by a non-root-admin user. */
dataset('userEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.users'], ['getJson', 'api.admin.users.view'], ['getJson', 'api.admin.users.external'], ['postJson', 'api.admin.users.store'], ['postJson', 'api.admin.users.disable-2fa'], ['putJson', 'api.admin.users.update'], ['delete', 'api.admin.users.delete']]);
test('returns paginated users with relationship counts', function (): void {
    $admin = $this->getAdminUser();
    $admin->forceFill(['username' => 'admin-list-contract-admin', 'email' => 'admin-list-contract-admin@example.test'])->save();
    $user = User::factory()->create(['username' => 'admin-list-contract-user', 'email' => 'admin-list-contract-user@example.test']);
    $server = $this->createServerModel(['owner_id' => $user->id]);
    Subuser::factory()->create(['user_id' => $user->id, 'server_id' => $server->id]);
    $user = $user->fresh()->loadCount('servers');
    $user->setAttribute('subuser_of_count', 1);

    $response = $this->getJson(route('api.admin.users', ['filter' => ['search' => 'admin-list-contract'], 'per_page' => 60]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ADMIN_USER_ATTRIBUTES], ['object', 'attributes' => ADMIN_USER_ATTRIBUTES]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJson(['object' => 'list', 'data' => [[], []], 'meta' => ['pagination' => ['total' => 2, 'count' => 2, 'per_page' => 60, 'current_page' => 1, 'total_pages' => 1]]]);
    Assert::assertArraySubset(['object' => 'user', 'attributes' => ['id' => $admin->id, 'external_id' => $admin->external_id, 'uuid' => $admin->uuid, 'username' => $admin->username, 'email' => $admin->email, 'language' => $admin->language, 'first_name' => $admin->name_first, 'last_name' => $admin->name_last, 'root_admin' => (bool) $admin->root_admin, '2fa' => (bool) $admin->use_totp]], collect($response->json('data'))->firstWhere('attributes.id', $admin->id), true);
    Assert::assertArraySubset(['object' => 'user', 'attributes' => ['id' => $user->id, 'external_id' => $user->external_id, 'uuid' => $user->uuid, 'username' => $user->username, 'email' => $user->email, 'language' => $user->language, 'first_name' => $user->name_first, 'last_name' => $user->name_last, 'root_admin' => (bool) $user->root_admin, '2fa' => (bool) $user->use_totp]], collect($response->json('data'))->firstWhere('attributes.id', $user->id), true);
    $attributes = collect($response->json('data'))->firstWhere('attributes.id', $user->id)['attributes'];
    expect($attributes['servers_count'])->toBe(1)
        ->and($attributes['subuser_of_count'])->toBe(1);
});
test('get users defaults to root admins first', function (): void {
    $regularUser = User::factory()->create(['root_admin' => false]);
    $rootAdmin = User::factory()->create(['root_admin' => true]);
    $response = $this->getJson(route('api.admin.users', ['per_page' => 100]));
    $response->assertStatus(Response::HTTP_OK);

    $emails = collect($response->json('data'))->pluck('attributes.email');
    expect($emails->search($rootAdmin->email))->toBeLessThan($emails->search($regularUser->email));
});
test('get users accepts data table sorts', function (string $sort): void {
    User::factory()->create(['username' => 'datatable-sort-a', 'email' => 'datatable-sort-a@example.test']);
    User::factory()->create(['username' => 'datatable-sort-z', 'email' => 'datatable-sort-z@example.test']);
    $response = $this->getJson(route('api.admin.users', ['filter' => ['search' => 'datatable-sort'], 'sort' => $sort, 'per_page' => 100]));
    $response->assertOk();
    $response->assertJsonCount(2, 'data');
})->with('userListSortsDataProvider');
test('get users searches identity fields', function (string $field): void {
    $user = User::factory()->create(['username' => 'parity-search-user', 'email' => 'parity-search@example.com']);
    User::factory()->create(['username' => 'unmatched-user', 'email' => 'unmatched@example.com']);
    $term = match ($field) {
        'username' => 'parity-search-user',
        'email' => 'parity-search@example.com',
        'uuid' => mb_substr($user->uuid, 0, 8),
    };
    $response = $this->getJson(route('api.admin.users', ['filter' => ['search' => $term], 'per_page' => 100]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.attributes.id', $user->id);
})->with('userSearchDataProvider');
test('get single user', function (): void {
    $user = User::factory()->create();
    $response = $this->getJson(route('api.admin.users.view', ['user' => $user->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ADMIN_USER_ATTRIBUTES]);
    $response->assertJson(['object' => 'user', 'attributes' => ['id' => $user->id, 'external_id' => $user->external_id, 'uuid' => $user->uuid, 'username' => $user->username, 'email' => $user->email, 'language' => $user->language, 'first_name' => $user->name_first, 'last_name' => $user->name_last, 'root_admin' => (bool) $user->root_admin, '2fa' => (bool) $user->use_totp]]);
});
test('get missing user', function (): void {
    $response = $this->getJson(route('api.admin.users.view', ['user' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('create user', function (): void {
    $response = $this->postJson(route('api.admin.users.store'), ['username' => 'testuser', 'email' => 'test@example.com', 'name_first' => 'Test', 'name_last' => 'User']);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonCount(3);
    $response->assertJsonStructure(['object', 'attributes' => ADMIN_USER_ATTRIBUTES, 'meta' => ['resource']]);
    $this->assertDatabaseHas('users', ['username' => 'testuser', 'email' => 'test@example.com']);
    $user = User::query()->where('username', 'testuser')->first();
    $response->assertJson(['object' => 'user', 'attributes' => ['id' => $user->id, 'external_id' => $user->external_id, 'uuid' => $user->uuid, 'username' => $user->username, 'email' => $user->email, 'language' => $user->language, 'first_name' => $user->name_first, 'last_name' => $user->name_last, 'root_admin' => (bool) $user->root_admin, '2fa' => (bool) $user->use_totp], 'meta' => ['resource' => route('api.admin.users.view', ['user' => $user->id])]], true);
});
test('update user', function (): void {
    $user = User::factory()->create();
    $response = $this->putJson(route('api.admin.users.update', ['user' => $user->id]), ['username' => 'new.test.name', 'email' => 'new@emailtest.com', 'name_first' => $user->name_first, 'name_last' => $user->name_last]);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ADMIN_USER_ATTRIBUTES]);
    $this->assertDatabaseHas('users', ['username' => 'new.test.name', 'email' => 'new@emailtest.com']);
    $user = $user->fresh();
    $response->assertJson(['object' => 'user', 'attributes' => ['id' => $user->id, 'external_id' => $user->external_id, 'uuid' => $user->uuid, 'username' => $user->username, 'email' => $user->email, 'language' => $user->language, 'first_name' => $user->name_first, 'last_name' => $user->name_last, 'root_admin' => (bool) $user->root_admin, '2fa' => (bool) $user->use_totp]]);
});
test('get user by external id', function (): void {
    $user = User::factory()->create(['external_id' => 'remote-123']);
    $response = $this->getJson(route('api.admin.users.external', ['external_id' => 'remote-123']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJson(['object' => 'user', 'attributes' => ['id' => $user->id, 'external_id' => $user->external_id, 'uuid' => $user->uuid, 'username' => $user->username, 'email' => $user->email, 'language' => $user->language, 'first_name' => $user->name_first, 'last_name' => $user->name_last, 'root_admin' => (bool) $user->root_admin, '2fa' => (bool) $user->use_totp]]);
});
test('get user by missing external id', function (): void {
    $response = $this->getJson(route('api.admin.users.external', ['external_id' => 'does-not-exist']));
    $this->assertNotFoundJson($response);
});
test('disable two factor', function (): void {
    $user = User::factory()->create(['use_totp' => true, 'totp_secret' => 'encrypted-secret']);
    $response = $this->postJson(route('api.admin.users.disable-2fa', ['user' => $user->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'use_totp' => false, 'totp_secret' => null]);
});
test('delete user', function (): void {
    $user = User::factory()->create();
    $this->assertDatabaseHas('users', ['id' => $user->id]);
    $response = $this->delete(route('api.admin.users.delete', ['user' => $user->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
test('cannot delete self', function (): void {
    $user = $this->getAdminUser();
    $response = $this->delete(route('api.admin.users.delete', ['user' => $user->id]));
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'DisplayException');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $user = User::factory()->create();
    // Pass both id and external_id so external-lookup routes resolve too; the 403 fires before model binding.
    $response = $this->{$method}(route($routeName, ['user' => $user->id, 'external_id' => 'forbidden']));
    $this->assertAccessDeniedJson($response);
})->with('userEndpointsDataProvider');
test('passwords shorter than eight characters are rejected', function (string $method, string $route): void {
    $user = User::factory()->create();
    $response = $this->{$method}(route($route, $route === 'api.admin.users.update' ? ['user' => $user->id] : []), ['username' => 'weakuser', 'email' => 'weak@example.com', 'name_first' => 'Weak', 'name_last' => 'Password', 'password' => 'abc']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonPath('errors.0.meta.source_field', 'password')->assertJsonPath('errors.0.meta.rule', 'min');
    $this->assertDatabaseMissing('users', ['username' => 'weakuser']);
})->with([['postJson', 'api.admin.users.store'], ['putJson', 'api.admin.users.update']]);
test('a blank password leaves the password unchanged', function (): void {
    $user = User::factory()->create();
    $hash = $user->password;
    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), ['username' => $user->username, 'email' => $user->email, 'name_first' => $user->name_first, 'name_last' => $user->name_last, 'password' => ''])->assertOk();
    expect($user->fresh()->password)->toBe($hash);
});
test('invalid payloads return validation errors', function (): void {
    $response = $this->postJson(route('api.admin.users.store'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    foreach (['email', 'username', 'name_first', 'name_last'] as $field) {
        $error = $errors->firstWhere('meta.source_field', $field);
        expect($error)->not->toBeNull("Expected a validation error for the [{$field}] field.");
        expect($error['meta']['rule'])->toBe('required');
        expect($error['detail'])->not->toBeEmpty();
    }
});
