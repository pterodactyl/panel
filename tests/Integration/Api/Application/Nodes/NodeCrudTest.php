<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Nodes\NodeCrudTest;

use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

uses(ApplicationApiIntegrationTestCase::class);
test('node configuration points wings at the configured panel url', function () {
    config(['app.url' => 'https://panel.example.com/']);
    $node = Node::factory()->withLocation()->create();

    $this->withServerVariables(['HTTP_HOST' => 'localhost:8123'])
        ->getJson("/api/application/nodes/{$node->id}/configuration")
        ->assertOk()
        ->assertJsonPath('remote', 'https://panel.example.com');
});
test('node can be created', function () {
    $location = Location::factory()->create();
    $response = $this->postJson('/api/application/nodes', ['name' => 'ApplicationNode', 'description' => 'Created through the application API.', 'location_id' => $location->id, 'public' => true, 'fqdn' => 'localhost', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes']);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonPath('object', 'node');
    $response->assertJsonPath('attributes.name', 'ApplicationNode');
    $this->assertDatabaseHas('nodes', ['name' => 'ApplicationNode', 'fqdn' => 'localhost']);
});
test('single node can be viewed', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $this->getJson("/api/application/nodes/{$node->id}")->assertOk()->assertJsonPath('object', 'node')->assertJsonPath('attributes.id', $node->id)->assertJsonPath('attributes.uuid', $node->uuid)->assertJsonPath('attributes.fqdn', $node->fqdn);
});
test('node configuration is returned', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $this->getJson("/api/application/nodes/{$node->id}/configuration")->assertOk()->assertJsonPath('uuid', $node->uuid)->assertJsonStructure(['debug', 'uuid', 'token_id', 'token', 'api' => ['host', 'port', 'ssl' => ['enabled', 'cert', 'key'], 'upload_limit'], 'system' => ['data', 'sftp' => ['bind_port']], 'remote']);
});
test('a key that can only read nodes cannot read node configuration', function (): void {
    $this->createNewDefaultApiKey($this->getApiUser(), ['r_nodes' => AdminAcl::READ]);
    $node = Node::factory()->for(Location::factory())->create();
    $this->assertAccessDeniedJson($this->getJson("/api/application/nodes/{$node->id}/configuration"));
    $this->getJson("/api/application/nodes/{$node->id}")->assertOk();
});
test('node without servers can be deleted', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $this->deleteJson("/api/application/nodes/{$node->id}")->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('nodes', ['id' => $node->id]);
});
test('node with servers cannot be deleted', function () {
    $server = $this->createServerModel();
    $this->deleteJson("/api/application/nodes/{$server->node_id}")->assertBadRequest();
    $this->assertDatabaseHas('nodes', ['id' => $server->node_id]);
});
test('empty server id filter returns only unassigned allocations', function () {
    $server = $this->createServerModel();
    $assigned = $server->allocation;
    $free = Allocation::factory()->create(['node_id' => $server->node_id]);
    $response = $this->getJson("/api/application/nodes/{$server->node_id}/allocations?filter[server_id]=");
    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['id' => $free->id, 'assigned' => false]);
    $response->assertJsonMissing(['id' => $assigned->id]);
});
test('allocations can be created for a node', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $this->postJson("/api/application/nodes/{$node->id}/allocations", ['ip' => '10.0.0.10', 'ports' => ['25565', '25570-25572']])->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseHas('allocations', ['node_id' => $node->id, 'ip' => '10.0.0.10', 'port' => 25565]);
    foreach ([25570, 25571, 25572] as $port) {
        $this->assertDatabaseHas('allocations', ['node_id' => $node->id, 'ip' => '10.0.0.10', 'port' => $port]);
    }
});
test('unassigned allocation can be deleted', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $allocation = Allocation::factory()->create(['node_id' => $node->id]);
    $this->deleteJson("/api/application/nodes/{$node->id}/allocations/{$allocation->id}")->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('allocations', ['id' => $allocation->id]);
});
test('assigned allocation cannot be deleted', function () {
    $server = $this->createServerModel();
    $allocation = $server->allocation;
    $this->deleteJson("/api/application/nodes/{$server->node_id}/allocations/{$allocation->id}")->assertBadRequest();
    $this->assertDatabaseHas('allocations', ['id' => $allocation->id]);
});
