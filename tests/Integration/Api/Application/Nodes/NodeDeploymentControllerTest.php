<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Nodes\NodeDeploymentControllerTest;

use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

uses(ApplicationApiIntegrationTestCase::class);
test('deployable nodes are transformed successfully', function () {
    $node = Node::factory()->for(Location::factory())->create(['public' => true, 'memory' => 1024, 'disk' => 10240]);
    $this->getJson('/api/application/nodes/deployable?memory=0&disk=0&page=1')->assertOk()->assertJsonFragment(['id' => $node->id, 'allocated_resources' => ['memory' => 0, 'disk' => 0]]);
});
test('allocated resources reflect assigned servers', function () {
    $server = $this->createServerModel();
    $node = Node::query()->findOrFail($server->node_id);
    $this->getJson("/api/application/nodes/deployable?memory=0&disk=0&page=1&location_ids[]={$node->location_id}")->assertOk()->assertJsonFragment(['id' => $node->id, 'allocated_resources' => ['memory' => $server->memory, 'disk' => $server->disk]]);
});
