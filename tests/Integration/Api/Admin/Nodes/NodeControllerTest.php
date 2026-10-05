<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Nodes\NodeControllerTest;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Assert;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonConfiguration;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root-administrator. */
dataset('nodeEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.nodes'], ['getJson', 'api.admin.nodes.view'], ['getJson', 'api.admin.nodes.configuration'], ['postJson', 'api.admin.nodes.store'], ['putJson', 'api.admin.nodes.update'], ['delete', 'api.admin.nodes.delete']]);
test('get nodes', function (): void {
    $nodes = Node::factory()->times(2)->for(Location::factory())->create();
    $response = $this->getJson(route('api.admin.nodes', ['per_page' => 60]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'uuid', 'name', 'fqdn', 'created_at', 'updated_at']], ['object', 'attributes' => ['id', 'uuid', 'name', 'fqdn', 'created_at', 'updated_at']]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJson(['object' => 'list', 'data' => [[], []], 'meta' => ['pagination' => ['total' => 2, 'count' => 2, 'per_page' => 60, 'current_page' => 1, 'total_pages' => 1]]]);
    Assert::assertArraySubset(['object' => 'node', 'attributes' => ['id' => $nodes[0]->id, 'uuid' => $nodes[0]->uuid, 'name' => $nodes[0]->name, 'fqdn' => $nodes[0]->fqdn, 'location_id' => $nodes[0]->location_id, 'memory' => $nodes[0]->memory, 'disk' => $nodes[0]->disk]], collect($response->json('data'))->firstWhere('attributes.id', $nodes[0]->id), true);
    Assert::assertArraySubset(['object' => 'node', 'attributes' => ['id' => $nodes[1]->id, 'uuid' => $nodes[1]->uuid, 'name' => $nodes[1]->name, 'fqdn' => $nodes[1]->fqdn, 'location_id' => $nodes[1]->location_id, 'memory' => $nodes[1]->memory, 'disk' => $nodes[1]->disk]], collect($response->json('data'))->firstWhere('attributes.id', $nodes[1]->id), true);
});
test('get nodes sorted by name', function (): void {
    $alpha = Node::factory()->for(Location::factory())->create(['name' => 'Alpha']);
    $bravo = Node::factory()->for(Location::factory())->create(['name' => 'Bravo']);
    $response = $this->getJson(route('api.admin.nodes', ['sort' => '-name', 'per_page' => 2]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('data.0.attributes.id', $bravo->id);
    $response->assertJsonPath('data.1.attributes.id', $alpha->id);
});
test('get single node', function (): void {
    $node = Node::factory()->for(Location::factory())->create();
    $response = $this->getJson(route('api.admin.nodes.view', ['node' => $node->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'uuid', 'name', 'fqdn', 'memory', 'disk', 'created_at', 'updated_at']]);
    $response->assertJson(['object' => 'node', 'attributes' => ['id' => $node->id, 'uuid' => $node->uuid, 'name' => $node->name, 'fqdn' => $node->fqdn, 'location_id' => $node->location_id, 'memory' => $node->memory, 'disk' => $node->disk]]);
});
test('get missing node', function (): void {
    $response = $this->getJson(route('api.admin.nodes.view', ['node' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('get node configuration', function (): void {
    $node = Node::factory()->for(Location::factory())->create();
    $response = $this->getJson(route('api.admin.nodes.configuration', ['node' => $node->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['uuid', 'token_id', 'token', 'api' => ['host', 'port', 'ssl' => ['enabled', 'cert', 'key'], 'upload_limit'], 'system' => ['data', 'sftp' => ['bind_port']], 'remote']);
    $response->assertJson(['uuid' => $node->uuid]);
});
test('create node', function (): void {
    $location = Location::factory()->create();
    $response = $this->postJson(route('api.admin.nodes.store'), ['name' => 'NewNode', 'description' => 'A brand new node.', 'location_id' => $location->id, 'public' => true, 'fqdn' => 'localhost', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes']);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'uuid', 'name', 'fqdn', 'created_at', 'updated_at'], 'meta' => ['resource']]);
    $this->assertDatabaseHas('nodes', ['name' => 'NewNode', 'fqdn' => 'localhost']);
    $node = Node::query()->where('name', 'NewNode')->first();
    $response->assertJson(['object' => 'node', 'attributes' => ['id' => $node->id, 'uuid' => $node->uuid, 'name' => $node->name, 'fqdn' => $node->fqdn, 'location_id' => $node->location_id, 'memory' => $node->memory, 'disk' => $node->disk], 'meta' => ['resource' => route('api.admin.nodes.view', ['node' => $node->id])]]);
});
test('create node rejects ip address when using https', function (): void {
    $location = Location::factory()->create();
    $response = $this->postJson(route('api.admin.nodes.store'), ['name' => 'IpNode', 'description' => 'A node with an invalid HTTPS address.', 'location_id' => $location->id, 'public' => true, 'fqdn' => '127.0.0.1', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'fqdn');
});
test('create node rejects http when panel request is secure', function (): void {
    $location = Location::factory()->create();
    // An explicitly https URL is required: the framework derives the HTTPS server
    // variable from the request URL, so withServerVariables() would be overridden.
    $response = $this->postJson('https://localhost'.route('api.admin.nodes.store', absolute: false), ['name' => 'HttpNode', 'description' => 'A node that would break secure browser websocket connections.', 'location_id' => $location->id, 'public' => true, 'fqdn' => 'localhost', 'scheme' => 'http', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'scheme');
});
test('update node', function (): void {
    $node = Node::factory()->for(Location::factory())->create();
    $location = Location::factory()->create();
    $fake = new FakeDaemonConfiguration;
    $response = $this->putJson(route('api.admin.nodes.update', ['node' => $node->id]), ['name' => 'UpdatedNode', 'description' => 'An updated description.', 'location_id' => $location->id, 'public' => true, 'fqdn' => 'localhost', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 4096, 'memory_overallocate' => 10, 'disk' => 40960, 'disk_overallocate' => 20, 'upload_size' => 256, 'daemon_listen' => 1102, 'daemon_sftp' => 1101, 'daemon_base' => '/var/lib/pterodactyl/volumes']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('object', 'node');
    $response->assertJsonPath('attributes.name', 'UpdatedNode');
    $response->assertJsonPath('attributes.fqdn', 'localhost');
    $response->assertJsonPath('attributes.memory', 4096);
    $response->assertJsonPath('attributes.daemon_listen', 1102);
    $response->assertJsonPath('attributes.daemon_sftp', 1101);
    $this->assertDatabaseHas('nodes', ['id' => $node->id, 'name' => 'UpdatedNode']);
    expect($node->refresh()->location_id)->toEqual($location->id);
    $fake->assertUpdated();
    Http::assertSent(fn (HttpRequest $request): bool => $request->hasHeader('Authorization', 'Bearer '.$node->getDecryptedKey()));
});
test('delete node', function (): void {
    $node = Node::factory()->for(Location::factory())->create();
    $this->assertDatabaseHas('nodes', ['id' => $node->id]);
    $response = $this->delete(route('api.admin.nodes.delete', ['node' => $node->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('nodes', ['id' => $node->id]);
});
test('delete node with servers', function (): void {
    $server = $this->createServerModel();
    $node = Node::query()->findOrFail($server->node_id);
    $response = $this->delete(route('api.admin.nodes.delete', ['node' => $node->id]));
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    // Error handler rolls the transaction back on render, so only assert the rejected status.
    $response->assertJsonPath('errors.0.code', 'HasActiveServersException');
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $node = Node::factory()->for(Location::factory())->create();
    $response = $this->{$method}(route($routeName, ['node' => $node->id]));
    $this->assertAccessDeniedJson($response);
})->with('nodeEndpointsDataProvider');
test('invalid payloads return validation errors', function (): void {
    $response = $this->postJson(route('api.admin.nodes.store'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    $error = $errors->firstWhere('meta.source_field', 'name');
    expect($error)->not->toBeNull('Expected a validation error for the [name] field.');
    expect($error['meta']['rule'])->toBe('required');
    expect($error['detail'])->not->toBeEmpty();
});
