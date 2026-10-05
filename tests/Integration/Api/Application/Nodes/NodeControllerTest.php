<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Nodes\NodeControllerTest;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

uses(ApplicationApiIntegrationTestCase::class);
test('node list returns allocated resources', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $server = $this->createServerModel(['node_id' => $node->id]);
    $this->getJson('/api/application/nodes?filter[uuid]='.$node->uuid)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.allocated_resources.memory', $server->memory)->assertJsonPath('data.0.attributes.allocated_resources.disk', $server->disk);
});
test('node list query count does not scale with results', function () {
    Node::factory()->for(Location::factory())->create();
    $this->getJson('/api/application/nodes')->assertOk();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/api/application/nodes')->assertOk();
    $oneNodeQueryCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    Node::factory()->for(Location::factory())->create();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/api/application/nodes')->assertOk();
    $twoNodeQueryCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    expect($twoNodeQueryCount)->toBe($oneNodeQueryCount);
});
