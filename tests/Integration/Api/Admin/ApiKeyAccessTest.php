<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\ApiKeyAccessTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Traits\Http\IntegrationJsonRequestAssertions;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class, IntegrationJsonRequestAssertions::class);
test('an application key cannot use the admin API, whatever its permissions', function (array $permissions): void {
    $admin = User::factory()->create(['root_admin' => true]);
    $key = ApiKey::factory()->create(['user_id' => $admin->id, 'key_type' => ApiKey::TYPE_APPLICATION, ...$permissions]);
    withKey($key);

    $denied = ['errors' => [['code' => 'AccessDeniedHttpException', 'status' => '403', 'detail' => 'You are attempting to use an application API key on an endpoint that does not accept them.']]];
    $this->getJson('/api/admin/users')->assertForbidden()->assertExactJson($denied);
    $this->postJson('/api/admin/tags', ['name' => 'Escalated', 'slug' => 'escalated'])->assertForbidden()->assertExactJson($denied);
    expect(Tag::query()->where('slug', 'escalated')->exists())->toBeFalse();
})->with([
    'node deployment key' => [['r_nodes' => 1]],
    'key with every permission' => [['r_servers' => 3, 'r_nodes' => 3, 'r_allocations' => 3, 'r_users' => 3, 'r_locations' => 3, 'r_eggs' => 3, 'r_database_hosts' => 3, 'r_server_databases' => 3]],
]);
test('a root administrator account key can use the admin API', function (): void {
    $admin = User::factory()->create(['root_admin' => true]);
    withKey(ApiKey::factory()->create(['user_id' => $admin->id, 'key_type' => ApiKey::TYPE_ACCOUNT]));

    $this->getJson('/api/admin/users')->assertOk();
});
test('an account key of a user who is not a root administrator cannot use the admin API', function (): void {
    $user = User::factory()->create(['root_admin' => false]);
    withKey(ApiKey::factory()->create(['user_id' => $user->id, 'key_type' => ApiKey::TYPE_ACCOUNT]));

    $this->assertAccessDeniedJson($this->getJson('/api/admin/users'));
});
test('a root administrator session can use the admin API', function (): void {
    $this->actingAs(User::factory()->create(['root_admin' => true]));

    $this->getJson('/api/admin/users')->assertOk();
});
test('a root administrator account key cannot install extensions', function (): void {
    $admin = User::factory()->create(['root_admin' => true]);
    withKey(ApiKey::factory()->create(['user_id' => $admin->id, 'key_type' => ApiKey::TYPE_ACCOUNT]));

    $this->postJson('/api/admin/extensions', [])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'AccessDeniedHttpException')
        ->assertJsonPath('errors.0.detail', 'This action can only be performed from an authenticated panel session, not with an API key.');
});
test('a root administrator account key cannot read node secrets', function (): void {
    $admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create();
    withKey(ApiKey::factory()->create(['user_id' => $admin->id, 'key_type' => ApiKey::TYPE_ACCOUNT]));

    $this->getJson("/api/admin/nodes/{$node->id}/configuration")->assertForbidden();
    $this->postJson("/api/admin/nodes/{$node->id}/deploy-token")->assertForbidden();
    $this->getJson("/api/application/nodes/{$node->id}/configuration")->assertForbidden();
    expect(ApiKey::query()->where('key_type', ApiKey::TYPE_APPLICATION)->exists())->toBeFalse();
});
test('a root administrator session can still reach the extension install endpoint', function (): void {
    $this->actingAs(User::factory()->create(['root_admin' => true]));

    $this->postJson('/api/admin/extensions', [])->assertUnprocessable();
});
/**
 * Send the key as a bearer token on the following requests.
 */
function withKey(ApiKey $key): void
{
    pterodactylTestCase()->withHeader('Accept', 'application/vnd.pterodactyl.v1+json')
        ->withHeader('Authorization', 'Bearer '.$key->identifier.decrypt($key->token));
}
