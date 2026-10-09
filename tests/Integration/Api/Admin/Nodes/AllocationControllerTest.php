<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Nodes\AllocationControllerTest;

use Illuminate\Http\Response;
use Illuminate\Testing\Assert;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
beforeEach(function (): void {
    $location = Location::factory()->create();
    $this->node = Node::factory()->create(['location_id' => $location->id]);
});
/** Endpoints that should return a 403 when accessed by a user that is not a root administrator. */
dataset('allocationEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.nodes.allocations'], ['getJson', 'api.admin.nodes.allocations.ips'], ['getJson', 'api.admin.nodes.allocations.available'], ['postJson', 'api.admin.nodes.allocations.store'], ['putJson', 'api.admin.nodes.allocations.update'], ['delete', 'api.admin.nodes.allocations.delete'], ['deleteJson', 'api.admin.nodes.allocations.bulk-delete'], ['deleteJson', 'api.admin.nodes.allocations.block']]);
test('get allocations', function (): void {
    $allocations = Allocation::factory()->times(2)->create(['node_id' => $this->node->id]);
    // An allocation on a different node should not leak into the response.
    $other = Node::factory()->create(['location_id' => $this->node->location_id]);
    Allocation::factory()->create(['node_id' => $other->id]);
    $response = $this->getJson(route('api.admin.nodes.allocations', ['node' => $this->node->id, 'per_page' => 60]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'ip', 'alias', 'port', 'notes', 'server_id', 'server_name', 'assigned']], ['object', 'attributes' => ['id', 'ip', 'alias', 'port', 'notes', 'server_id', 'server_name', 'assigned']]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJson(['object' => 'list', 'meta' => ['pagination' => ['total' => 2, 'count' => 2, 'per_page' => 60, 'current_page' => 1, 'total_pages' => 1]]]);
    Assert::assertArraySubset(['object' => 'allocation', 'attributes' => ['id' => $allocations[0]->id, 'ip' => $allocations[0]->ip, 'port' => $allocations[0]->port, 'alias' => $allocations[0]->ip_alias, 'server_id' => $allocations[0]->server_id, 'assigned' => $allocations[0]->server_id !== null]], collect($response->json('data'))->firstWhere('attributes.id', $allocations[0]->id), true);
    Assert::assertArraySubset(['object' => 'allocation', 'attributes' => ['id' => $allocations[1]->id, 'ip' => $allocations[1]->ip, 'port' => $allocations[1]->port, 'alias' => $allocations[1]->ip_alias, 'server_id' => $allocations[1]->server_id, 'assigned' => $allocations[1]->server_id !== null]], collect($response->json('data'))->firstWhere('attributes.id', $allocations[1]->id), true);
});
test('assigned allocation returns server name', function (): void {
    $server = $this->createServerModel(['node_id' => $this->node->id]);
    $assigned = $server->allocation;
    $unassigned = Allocation::factory()->create(['node_id' => $this->node->id]);
    $response = $this->getJson(route('api.admin.nodes.allocations', ['node' => $this->node->id, 'per_page' => 60]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonFragment(['id' => $assigned->id, 'server_id' => $server->id, 'server_name' => $server->name, 'assigned' => true]);
    $response->assertJsonFragment(['id' => $unassigned->id, 'server_id' => null, 'server_name' => null, 'assigned' => false]);
});
test('empty server id filter returns only unassigned allocations', function (): void {
    $server = $this->createServerModel(['node_id' => $this->node->id]);
    $assigned = $server->allocation;
    $free = Allocation::factory()->create(['node_id' => $this->node->id]);
    $response = $this->getJson(route('api.admin.nodes.allocations', ['node' => $this->node->id, 'filter' => ['server_id' => '']]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['id' => $free->id, 'assigned' => false]);
    $response->assertJsonMissing(['id' => $assigned->id]);
});
test('server id filter returns only that server allocations', function (): void {
    $server = $this->createServerModel(['node_id' => $this->node->id]);
    $assigned = $server->allocation;
    $free = Allocation::factory()->create(['node_id' => $this->node->id]);
    $response = $this->getJson(route('api.admin.nodes.allocations', ['node' => $this->node->id, 'filter' => ['server_id' => $server->id]]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['id' => $assigned->id, 'assigned' => true]);
    $response->assertJsonMissing(['id' => $free->id]);
});
test('unique ips', function (): void {
    Allocation::factory()->times(2)->create(['node_id' => $this->node->id, 'ip' => '10.0.0.9']);
    Allocation::factory()->create(['node_id' => $this->node->id, 'ip' => '10.0.0.8']);
    $response = $this->getJson(route('api.admin.nodes.allocations.ips', ['node' => $this->node->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertExactJson(['data' => ['10.0.0.8', '10.0.0.9']]);
});
test('available allocations', function (): void {
    $server = $this->createServerModel(['node_id' => $this->node->id]);
    $assigned = $server->allocation;
    $free = Allocation::factory()->create(['node_id' => $this->node->id]);
    $response = $this->getJson(route('api.admin.nodes.allocations.available', ['node' => $this->node->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['id' => $free->id, 'assigned' => false]);
    $response->assertJsonMissing(['id' => $assigned->id]);
});
test('create allocations', function (): void {
    $response = $this->postJson(route('api.admin.nodes.allocations.store', ['node' => $this->node->id]), ['ip' => ['192.168.1.1'], 'alias' => 'test-alias', 'ports' => ['25565', '25566']]);
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseHas('allocations', ['node_id' => $this->node->id, 'ip' => '192.168.1.1', 'port' => 25565, 'ip_alias' => 'test-alias']);
    $this->assertDatabaseHas('allocations', ['node_id' => $this->node->id, 'ip' => '192.168.1.1', 'port' => 25566, 'ip_alias' => 'test-alias']);
});
test('create allocations from range', function (): void {
    $response = $this->postJson(route('api.admin.nodes.allocations.store', ['node' => $this->node->id]), ['ip' => ['192.168.1.2'], 'ports' => ['5000-5002']]);
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    foreach ([5000, 5001, 5002] as $port) {
        $this->assertDatabaseHas('allocations', ['node_id' => $this->node->id, 'ip' => '192.168.1.2', 'port' => $port]);
    }
});
test('update allocation alias', function (): void {
    $allocation = Allocation::factory()->create(['node_id' => $this->node->id, 'ip_alias' => null]);
    $response = $this->putJson(route('api.admin.nodes.allocations.update', ['node' => $this->node->id, 'allocation' => $allocation->id]), ['alias' => 'updated-alias']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'ip', 'alias', 'port', 'notes', 'server_id', 'server_name', 'assigned']]);
    $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'ip_alias' => 'updated-alias']);
    $response->assertJson(['object' => 'allocation', 'attributes' => ['id' => $allocation->id, 'ip' => $allocation->ip, 'port' => $allocation->port, 'alias' => 'updated-alias', 'server_id' => $allocation->server_id, 'assigned' => $allocation->server_id !== null]]);
});
test('update allocation alias can be cleared', function (): void {
    $allocation = Allocation::factory()->create(['node_id' => $this->node->id, 'ip_alias' => 'existing']);
    $response = $this->putJson(route('api.admin.nodes.allocations.update', ['node' => $this->node->id, 'allocation' => $allocation->id]), ['alias' => '']);
    $response->assertStatus(Response::HTTP_OK);
    $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'ip_alias' => null]);
});
test('delete allocation', function (): void {
    $allocation = Allocation::factory()->create(['node_id' => $this->node->id]);
    $response = $this->delete(route('api.admin.nodes.allocations.delete', ['node' => $this->node->id, 'allocation' => $allocation->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('allocations', ['id' => $allocation->id]);
});
test('cannot delete assigned allocation', function (): void {
    $server = $this->createServerModel(['node_id' => $this->node->id]);
    $allocation = $server->allocation;
    $response = $this->delete(route('api.admin.nodes.allocations.delete', ['node' => $this->node->id, 'allocation' => $allocation->id]));
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    // Error handler rolls the transaction back on render, so only assert the rejected status.
    $response->assertJsonPath('errors.0.code', 'ServerUsingAllocationException');
});
test('bulk delete allocations', function (): void {
    $allocations = Allocation::factory()->times(3)->create(['node_id' => $this->node->id]);
    $keep = Allocation::factory()->create(['node_id' => $this->node->id]);
    $response = $this->deleteJson(route('api.admin.nodes.allocations.bulk-delete', ['node' => $this->node->id]), ['ids' => [$allocations[0]->id, $allocations[1]->id, $allocations[2]->id]]);
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    foreach ($allocations as $allocation) {
        $this->assertDatabaseMissing('allocations', ['id' => $allocation->id]);
    }

    $this->assertDatabaseHas('allocations', ['id' => $keep->id]);
});
test('ip block delete allocations', function (): void {
    $block = Allocation::factory()->times(2)->create(['node_id' => $this->node->id, 'ip' => '10.0.0.5']);
    $other = Allocation::factory()->create(['node_id' => $this->node->id, 'ip' => '10.0.0.6']);
    $assigned = $this->createServerModel(['node_id' => $this->node->id])->allocation;
    $assigned->forceFill(['ip' => '10.0.0.5'])->save();
    $response = $this->deleteJson(route('api.admin.nodes.allocations.block', ['node' => $this->node->id]), ['ip' => '10.0.0.5']);
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    foreach ($block as $allocation) {
        $this->assertDatabaseMissing('allocations', ['id' => $allocation->id]);
    }

    $this->assertDatabaseHas('allocations', ['id' => $other->id]);
    $this->assertDatabaseHas('allocations', ['id' => $assigned->id]);
});
test('bulk delete requires ids', function (): void {
    $response = $this->deleteJson(route('api.admin.nodes.allocations.bulk-delete', ['node' => $this->node->id]), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'ids');
});
test('ip block delete requires ip', function (): void {
    $response = $this->deleteJson(route('api.admin.nodes.allocations.block', ['node' => $this->node->id]), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'ip');
});
test('allocation not on node returns404', function (): void {
    $other = Node::factory()->create(['location_id' => $this->node->location_id]);
    $allocation = Allocation::factory()->create(['node_id' => $other->id]);
    $response = $this->delete(route('api.admin.nodes.allocations.delete', ['node' => $this->node->id, 'allocation' => $allocation->id]));
    $this->assertNotFoundJson($response);
});
test('missing node returns404', function (): void {
    $response = $this->getJson(route('api.admin.nodes.allocations', ['node' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('invalid payloads return validation errors', function (): void {
    $response = $this->postJson(route('api.admin.nodes.allocations.store', ['node' => $this->node->id]), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    $error = $errors->firstWhere('meta.source_field', 'ip');
    expect($error)->not->toBeNull('Expected a validation error for the [ip] field.');
    expect($error['meta']['rule'])->toBe('required');
    expect($error['detail'])->not->toBeEmpty();

    $error = $errors->firstWhere('meta.source_field', 'ports');
    expect($error)->not->toBeNull('Expected a validation error for the [ports] field.');
    expect($error['meta']['rule'])->toBe('required');
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $allocation = Allocation::factory()->create(['node_id' => $this->node->id]);
    $response = $this->{$method}(route($routeName, ['node' => $this->node->id, 'allocation' => $allocation->id]));
    $this->assertAccessDeniedJson($response);
})->with('allocationEndpointsDataProvider');
test('creating allocations records the address under a non reserved property', function (): void {
    $this->postJson(route('api.admin.nodes.allocations.store', ['node' => $this->node->id]), ['ip' => ['10.0.0.1'], 'ports' => ['25565']])->assertStatus(Response::HTTP_NO_CONTENT);

    $properties = ActivityLog::query()->where('event', 'admin:node-allocation.create')->latest('id')->firstOrFail()->propertyValues();

    expect($properties)->toBe(['address' => ['10.0.0.1'], 'ports' => ['25565']]);
});
test('allocation creation rejects associative address and port arrays', function (string $field): void {
    $payload = ['ip' => ['10.0.0.1'], 'ports' => ['25565']];
    $payload[$field] = ['first' => $payload[$field][0]];

    $this->postJson(route('api.admin.nodes.allocations.store', ['node' => $this->node->id]), $payload)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', $field);

    $this->assertDatabaseMissing('allocations', ['node_id' => $this->node->id]);
})->with(['ip', 'ports']);
test('deleting an ip block records a distinct event from deleting one allocation', function (): void {
    $allocation = Allocation::factory()->create(['node_id' => $this->node->id, 'ip' => '10.0.0.5', 'port' => 25565]);
    Allocation::factory()->create(['node_id' => $this->node->id, 'ip' => '10.0.0.6']);

    $this->delete(route('api.admin.nodes.allocations.delete', ['node' => $this->node->id, 'allocation' => $allocation->id]))->assertStatus(Response::HTTP_NO_CONTENT);
    $this->deleteJson(route('api.admin.nodes.allocations.block', ['node' => $this->node->id]), ['ip' => '10.0.0.6'])->assertStatus(Response::HTTP_NO_CONTENT);

    $properties = fn (string $event): array => ActivityLog::query()->where('event', $event)->latest('id')->firstOrFail()->propertyValues();

    expect($properties('admin:node-allocation.delete'))->toBe(['address' => '10.0.0.5', 'port' => 25565]);
    expect($properties('admin:node-allocation.delete-block'))->toBe(['address' => '10.0.0.6']);
});
