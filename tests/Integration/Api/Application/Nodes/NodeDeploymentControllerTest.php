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
test('deployment criteria are read from query strings', function () {
    $location = Location::factory()->create();
    $first = Node::factory()->for($location)->create(['public' => true, 'memory' => 4096, 'disk' => 40960]);
    $second = Node::factory()->for($location)->create(['public' => true, 'memory' => 4096, 'disk' => 40960]);
    Node::factory()->for($location)->create(['public' => true, 'memory' => 512, 'disk' => 40960]);
    Node::factory()->for(Location::factory())->create(['public' => true, 'memory' => 4096, 'disk' => 40960]);

    $unpaginated = $this->getJson("/api/application/nodes/deployable?memory=1024&disk=1024&location_ids[]={$location->id}")->assertOk();
    expect(collect($unpaginated->json('data'))->pluck('attributes.id')->sort()->values()->all())->toBe([$first->id, $second->id]);
    $unpaginated->assertJsonMissingPath('meta.pagination');

    $this->getJson("/api/application/nodes/deployable?memory=1024&disk=1024&page=2&per_page=1&location_ids[]={$location->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.pagination.per_page', 1)
        ->assertJsonPath('meta.pagination.current_page', 2)
        ->assertJsonPath('meta.pagination.total', 2);

    $this->getJson('/api/application/nodes/deployable?memory=abc&disk=1024&location_ids[]=one')
        ->assertUnprocessable()
        ->assertJsonCount(2, 'errors')
        ->assertJsonPath('errors.0.meta.source_field', 'memory')
        ->assertJsonPath('errors.1.meta.source_field', 'location_ids.0');
});
test('allocated resources reflect assigned servers', function () {
    $server = $this->createServerModel();
    $node = Node::query()->findOrFail($server->node_id);
    $this->getJson("/api/application/nodes/deployable?memory=0&disk=0&page=1&location_ids[]={$node->location_id}")->assertOk()->assertJsonFragment(['id' => $node->id, 'allocated_resources' => ['memory' => $server->memory, 'disk' => $server->disk]]);
});
