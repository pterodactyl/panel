<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Servers\ServerControllerTest;

use Illuminate\Http\Response;
use Illuminate\Testing\Assert;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root administrator. */
dataset('serverEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.servers'], ['getJson', 'api.admin.servers.view'], ['getJson', 'api.admin.servers.external'], ['postJson', 'api.admin.servers.store'], ['deleteJson', 'api.admin.servers.delete'], ['putJson', 'api.admin.servers.details'], ['putJson', 'api.admin.servers.build'], ['putJson', 'api.admin.servers.startup'], ['postJson', 'api.admin.servers.suspension'], ['postJson', 'api.admin.servers.reinstall'], ['postJson', 'api.admin.servers.rebuild'], ['getJson', 'api.admin.servers.transfer-progress']]);
test('get servers', function (): void {
    $server = $this->createServerModel();
    $response = $this->getJson(route('api.admin.servers', ['per_page' => 60]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'uuid', 'identifier', 'name', 'status', 'user', 'node', 'created_at', 'updated_at']]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJson(['object' => 'list', 'data' => [[]], 'meta' => ['pagination' => ['total' => 1, 'count' => 1, 'per_page' => 60, 'current_page' => 1, 'total_pages' => 1]]]);
    Assert::assertArraySubset(['object' => 'server', 'attributes' => ['id' => $server->id, 'external_id' => $server->external_id, 'uuid' => $server->uuid, 'name' => $server->name, 'description' => $server->description, 'status' => $server->status, 'identifier' => $server->uuidShort, 'user' => $server->owner_id, 'node' => $server->node_id, 'allocation' => $server->allocation_id, 'egg' => $server->egg_id, 'limits' => ['memory' => $server->memory, 'disk' => $server->disk, 'cpu' => $server->cpu], 'feature_limits' => ['databases' => $server->database_limit, 'allocations' => $server->allocation_limit, 'backups' => $server->backup_limit], 'container' => ['startup_command' => $server->startup, 'image' => $server->image]]], collect($response->json('data'))->firstWhere('attributes.id', $server->id), true);
});
test('get servers filtered by node', function (): void {
    $server = $this->createServerModel();
    $this->createServerModel();
    $response = $this->getJson(route('api.admin.servers', ['filter' => ['node_id' => $server->node_id]]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.attributes.id', $server->id);
});
test('get servers sorted by name', function (): void {
    $alpha = $this->createServerModel(['name' => 'Alpha']);
    $bravo = $this->createServerModel(['name' => 'Bravo']);
    $response = $this->getJson(route('api.admin.servers', ['sort' => '-name', 'per_page' => 2]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('data.0.attributes.id', $bravo->id);
    $response->assertJsonPath('data.1.attributes.id', $alpha->id);
});
test('get servers filtered by broad search', function (): void {
    /** @var User $owner */
    $owner = User::factory()->create(['username' => 'blade-owner', 'email' => 'blade-owner@example.com']);
    $server = $this->createServerModel(['owner_id' => $owner->id, 'name' => 'Blade Parity', 'uuidShort' => 'b1ade123', 'external_id' => 'legacy-external']);
    $this->createServerModel(['name' => 'Other Server']);
    foreach (['blade', 'b1ade123', 'legacy-external', 'blade-owner', 'blade-owner@example.com'] as $term) {
        $response = $this->getJson(route('api.admin.servers', ['filter' => ['*' => $term]]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.id', $server->id);
    }
});
test('get single server', function (): void {
    $server = $this->createServerModel();
    $response = $this->getJson(route('api.admin.servers.view', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'uuid', 'identifier', 'name', 'status', 'limits', 'feature_limits', 'container', 'created_at', 'updated_at']]);
    $response->assertJson(['object' => 'server', 'attributes' => ['id' => $server->id, 'external_id' => $server->external_id, 'uuid' => $server->uuid, 'name' => $server->name, 'description' => $server->description, 'status' => $server->status, 'identifier' => $server->uuidShort, 'user' => $server->owner_id, 'node' => $server->node_id, 'allocation' => $server->allocation_id, 'egg' => $server->egg_id, 'limits' => ['memory' => $server->memory, 'disk' => $server->disk, 'cpu' => $server->cpu], 'feature_limits' => ['databases' => $server->database_limit, 'allocations' => $server->allocation_limit, 'backups' => $server->backup_limit], 'container' => ['startup_command' => $server->startup, 'image' => $server->image]]]);
});
test('get single server by short uuid', function (): void {
    $server = $this->createServerModel(['uuidShort' => 'deadbeef']);
    $response = $this->getJson(route('api.admin.servers.view', ['server' => $server->uuidShort]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJson(['object' => 'server', 'attributes' => ['id' => $server->id, 'external_id' => $server->external_id, 'uuid' => $server->uuid, 'name' => $server->name, 'description' => $server->description, 'status' => $server->status, 'identifier' => $server->uuidShort, 'user' => $server->owner_id, 'node' => $server->node_id, 'allocation' => $server->allocation_id, 'egg' => $server->egg_id, 'limits' => ['memory' => $server->memory, 'disk' => $server->disk, 'cpu' => $server->cpu], 'feature_limits' => ['databases' => $server->database_limit, 'allocations' => $server->allocation_limit, 'backups' => $server->backup_limit], 'container' => ['startup_command' => $server->startup, 'image' => $server->image]]]);
});
test('includes can be loaded', function (): void {
    $server = $this->createServerModel();
    $response = $this->getJson(route('api.admin.servers.view', ['server' => $server->id, 'include' => 'user,allocations,node,location']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['object', 'attributes' => ['relationships' => ['user' => ['object', 'attributes'], 'allocations' => ['object', 'data'], 'node' => ['object', 'attributes'], 'location' => ['object', 'attributes']]]]);
    $response->assertJsonPath('attributes.relationships.user.attributes.id', $server->owner_id);
    $response->assertJsonPath('attributes.relationships.node.attributes.id', $server->node_id);
    // 'nest' is a retired include; unknown include names must still be ignored.
    $ignored = $this->getJson(route('api.admin.servers.view', ['server' => $server->id, 'include' => 'nest']));
    $ignored->assertStatus(Response::HTTP_OK);
});
test('get missing server', function (): void {
    $response = $this->getJson(route('api.admin.servers.view', ['server' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('create server', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var Location $location */
    $location = Location::factory()->create();
    /** @var Node $node */
    $node = Node::factory()->create(['location_id' => $location->id]);
    /** @var Allocation $allocation */
    $allocation = Allocation::factory()->create(['node_id' => $node->id]);
    /** @var Egg $egg */
    $egg = Egg::query()->where('author', 'support@pterodactyl.io')->where('name', 'Bungeecord')->firstOrFail();
    $fake = new FakeDaemonServer;
    $response = $this->postJson(route('api.admin.servers.store'), ['name' => 'NewServer', 'description' => 'A brand new server.', 'owner_id' => $user->id, 'egg_id' => $egg->id, 'docker_image' => 'java:8', 'startup' => 'java -jar server.jar', 'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar'], 'memory' => 512, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 0, 'threads' => null, 'database_limit' => 0, 'allocation_limit' => 0, 'backup_limit' => 0, 'primary_allocation_id' => $allocation->id]);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'uuid', 'identifier', 'name', 'created_at', 'updated_at'], 'meta' => ['resource']]);
    $this->assertDatabaseHas('servers', ['name' => 'NewServer', 'owner_id' => $user->id]);
    $server = Server::query()->where('name', 'NewServer')->firstOrFail();
    $response->assertJson(['object' => 'server', 'meta' => ['resource' => route('api.admin.servers.view', ['server' => $server->id])]]);
    $fake->assertCreated();
});
test('delete server', function (): void {
    $server = $this->createServerModel();
    $this->assertDatabaseHas('servers', ['id' => $server->id]);
    $fake = new FakeDaemonServer;
    $response = $this->delete(route('api.admin.servers.delete', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    $fake->assertDeleted();
});
test('force delete server via dedicated route', function (): void {
    $server = $this->createServerModel();
    $fake = new FakeDaemonServer;
    $this->delete(route('api.admin.servers.delete.force', ['server' => $server->id]))->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    $fake->assertDeleted();
});
test('invalid payloads return validation errors', function (): void {
    $response = $this->postJson(route('api.admin.servers.store'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    $error = $errors->firstWhere('meta.source_field', 'name');
    expect($error)->not->toBeNull('Expected a validation error for the [name] field.');
    expect($error['meta']['rule'])->toBe('required');
    expect($error['detail'])->not->toBeEmpty();
});
test('get server by external id', function (): void {
    $server = $this->createServerModel(['external_id' => 'srv-9000']);
    $response = $this->getJson(route('api.admin.servers.external', ['external_id' => 'srv-9000']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJson(['object' => 'server', 'attributes' => ['id' => $server->id, 'external_id' => $server->external_id, 'uuid' => $server->uuid, 'name' => $server->name, 'description' => $server->description, 'status' => $server->status, 'identifier' => $server->uuidShort, 'user' => $server->owner_id, 'node' => $server->node_id, 'allocation' => $server->allocation_id, 'egg' => $server->egg_id, 'limits' => ['memory' => $server->memory, 'disk' => $server->disk, 'cpu' => $server->cpu], 'feature_limits' => ['databases' => $server->database_limit, 'allocations' => $server->allocation_limit, 'backups' => $server->backup_limit], 'container' => ['startup_command' => $server->startup, 'image' => $server->image]]]);
});
test('get server by missing external id', function (): void {
    $response = $this->getJson(route('api.admin.servers.external', ['external_id' => 'does-not-exist']));
    $this->assertNotFoundJson($response);
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $server = $this->createServerModel();
    // Pass both id and external_id so external-lookup routes resolve too; the 403 fires before model binding.
    $response = $this->{$method}(route($routeName, ['server' => $server->id, 'external_id' => 'forbidden']));
    $this->assertAccessDeniedJson($response);
})->with('serverEndpointsDataProvider');
