<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Nodes\NodeController\UpdateNodeTest;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonConfiguration;

uses(ApplicationApiIntegrationTestCase::class);
test('can update node properties', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $location = Location::factory()->create();
    $fake = new FakeDaemonConfiguration;
    $this->patchJson(route('api.application.nodes.update', ['node' => $node]), ['name' => 'New Name', 'description' => 'New Description', 'location_id' => $location->id, 'fqdn' => 'new.example.com', 'scheme' => 'https', 'memory' => 100, 'memory_overallocate' => 10, 'disk' => 200, 'disk_overallocate' => 20, 'daemon_sftp' => 1101, 'daemon_listen' => 1102])->assertOk()->assertJsonPath('object', 'node')->assertJsonPath('attributes.name', 'New Name')->assertJsonPath('attributes.description', 'New Description')->assertJsonPath('attributes.fqdn', 'new.example.com')->assertJsonPath('attributes.scheme', 'https')->assertJsonPath('attributes.memory', 100)->assertJsonPath('attributes.memory_overallocate', 10)->assertJsonPath('attributes.disk', 200)->assertJsonPath('attributes.disk_overallocate', 20)->assertJsonPath('attributes.daemon_sftp', 1101)->assertJsonPath('attributes.daemon_listen', 1102);
    expect($node->refresh()->location_id)->toEqual($location->id);
    $fake->assertUpdated();
    Http::assertSent(fn (HttpRequest $request): bool => $request->hasHeader('Authorization', 'Bearer '.$node->getDecryptedKey()));
});
