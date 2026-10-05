<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Servers\ServerTransferControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonTransfer;

uses(AdminApiIntegrationTestCase::class);
test('server can be transferred', function (bool $nullAdditional): void {
    $server = $this->createServerModel();
    /** @var Node $targetNode */
    $targetNode = Node::factory()->create(['location_id' => $server->node->location_id, 'memory' => 102400, 'disk' => 102400]);
    /** @var Allocation $allocation */
    $allocation = Allocation::factory()->create(['node_id' => $targetNode->id, 'server_id' => null]);
    $fake = new FakeDaemonTransfer;
    $payload = ['node_id' => $targetNode->id, 'allocation_id' => $allocation->id];
    if ($nullAdditional) {
        $payload['allocation_additional'] = null;
    }

    $response = $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), $payload);
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseHas('server_transfers', ['server_id' => $server->id, 'old_node' => $server->node_id, 'new_node' => $targetNode->id, 'new_allocation' => $allocation->id]);
    // The chosen allocation now belongs to the server so it cannot be claimed mid-transfer.
    $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'server_id' => $server->id]);
    $fake->assertNotified();
})->with(['omitted additional allocations' => false, 'null additional allocations' => true]);
test('additional allocations are assigned', function (bool $stringIdentifiers): void {
    $server = $this->createServerModel();
    /** @var Node $targetNode */
    $targetNode = Node::factory()->create(['location_id' => $server->node->location_id, 'memory' => 102400, 'disk' => 102400]);
    /** @var Allocation $primary */
    $primary = Allocation::factory()->create(['node_id' => $targetNode->id, 'server_id' => null]);
    /** @var Allocation $additional */
    $additional = Allocation::factory()->create(['node_id' => $targetNode->id, 'server_id' => null]);
    $fake = new FakeDaemonTransfer;
    $response = $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), ['node_id' => $targetNode->id, 'allocation_id' => $primary->id, 'allocation_additional' => [$stringIdentifiers ? (string) $additional->id : $additional->id]]);
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseHas('server_transfers', ['server_id' => $server->id, 'old_node' => $server->node_id, 'new_node' => $targetNode->id, 'new_allocation' => $primary->id]);
    $this->assertDatabaseHas('allocations', ['id' => $primary->id, 'server_id' => $server->id]);
    $this->assertDatabaseHas('allocations', ['id' => $additional->id, 'server_id' => $server->id]);
    $fake->assertNotified();
})->with(['integer identifiers' => false, 'numeric string identifiers' => true]);

test('transfer rejects associative additional allocations with 422', function (): void {
    $server = $this->createServerModel();
    $target = Node::factory()->create(['location_id' => $server->node->location_id, 'memory' => 102400, 'disk' => 102400]);
    $primary = Allocation::factory()->for($target)->create(['server_id' => null]);
    $additional = Allocation::factory()->for($target)->create(['server_id' => null]);

    $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), [
        'node_id' => $target->id,
        'allocation_id' => $primary->id,
        'allocation_additional' => ['selected' => $additional->id],
    ])->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'allocation_additional');

    $this->assertDatabaseMissing('server_transfers', ['server_id' => $server->id]);
    $this->assertDatabaseHas('allocations', ['id' => $additional->id, 'server_id' => null]);
});

test('transfer rejects malformed additional allocation identifiers with 422', function (bool|float|string|null $value): void {
    $server = $this->createServerModel();
    $target = Node::factory()->create(['location_id' => $server->node->location_id, 'memory' => 102400, 'disk' => 102400]);
    $primary = Allocation::factory()->for($target)->create(['server_id' => null]);

    $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), [
        'node_id' => $target->id,
        'allocation_id' => $primary->id,
        'allocation_additional' => [$value],
    ], options: JSON_PRESERVE_ZERO_FRACTION)->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'allocation_additional.0');

    $this->assertDatabaseMissing('server_transfers', ['server_id' => $server->id]);
})->with(['boolean' => true, 'whole float' => 1.0, 'empty string' => '', 'null' => [null]]);

test('null additional allocations do not bypass target node validation', function (): void {
    $server = $this->createServerModel();
    $target = Node::factory()->create(['location_id' => $server->node->location_id, 'memory' => 102400, 'disk' => 102400]);
    $wrongNodeAllocation = Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => null]);

    $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), [
        'node_id' => $target->id,
        'allocation_id' => $wrongNodeAllocation->id,
        'allocation_additional' => null,
    ])->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'allocation_id');

    $this->assertDatabaseMissing('server_transfers', ['server_id' => $server->id]);
    $this->assertDatabaseHas('allocations', ['id' => $wrongNodeAllocation->id, 'server_id' => null]);
});
test('server cannot be transferred to same node', function (): void {
    $server = $this->createServerModel();
    /** @var Allocation $allocation */
    $allocation = Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => null]);
    $response = $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), ['node_id' => $server->node_id, 'allocation_id' => $allocation->id]);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'node_id');
    $this->assertDatabaseMissing('server_transfers', ['server_id' => $server->id]);
});
test('transfer rejects assigned allocation', function (): void {
    $server = $this->createServerModel();
    /** @var Node $targetNode */
    $targetNode = Node::factory()->create(['location_id' => $server->node->location_id, 'memory' => 102400, 'disk' => 102400]);
    /** @var Server $otherServer */
    $otherServer = $this->createServerModel(['node_id' => $targetNode->id]);
    $response = $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), ['node_id' => $targetNode->id, 'allocation_id' => $otherServer->allocation_id]);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'allocation_id');
    $this->assertDatabaseMissing('server_transfers', ['server_id' => $server->id]);
});
test('invalid payloads return validation errors', function (): void {
    $server = $this->createServerModel();
    $response = $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    $errors = collect($response->json('errors'));
    expect($errors->firstWhere('meta.source_field', 'node_id'))->not->toBeNull();
    expect($errors->firstWhere('meta.source_field', 'allocation_id'))->not->toBeNull();
});
test('transfer missing server', function (): void {
    $response = $this->postJson(route('api.admin.servers.transfer', ['server' => 'nil']), []);
    $this->assertNotFoundJson($response);
});
test('non admin forbidden', function (): void {
    $server = $this->createServerModel();
    $this->actingAsNonAdmin();
    $response = $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), []);
    $this->assertAccessDeniedJson($response);
});
