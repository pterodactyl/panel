<?php

namespace Pterodactyl\Tests\Integration\Api\Application\Nodes\NodeController;

use Mockery\MockInterface;
use Pterodactyl\Models\Node;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Location;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

class UpdateNodeTest extends ApplicationApiIntegrationTestCase
{
    public function testCanUpdateNodeProperties(): void
    {
        $node = Node::factory()->for(Location::factory())->create();
        $location = Location::factory()->create();

        $this->mock(DaemonConfigurationRepository::class, function (MockInterface $mock) use ($node) {
            $mock->expects('setNode')->with(\Mockery::on(fn ($value) => $value->is($node)))->andReturnSelf();
            $mock->expects('update')->withAnyArgs()->andReturn(
                new Response()
            );
        });

        $this->patchJson(route('api.application.nodes.update', ['node' => $node]), [
            'name' => 'New Name',
            'description' => 'New Description',
            'location_id' => $location->id,
            'fqdn' => 'new.example.com',
            'scheme' => 'https',
            'memory' => 100,
            'memory_overallocate' => 10,
            'disk' => 200,
            'disk_overallocate' => 20,
            'daemon_sftp' => 1101,
            'daemon_listen' => 1102,
        ])
            ->assertOk()
            ->assertJsonPath('object', 'node')
            ->assertJsonPath('attributes.name', 'New Name')
            ->assertJsonPath('attributes.description', 'New Description')
            ->assertJsonPath('attributes.fqdn', 'new.example.com')
            ->assertJsonPath('attributes.scheme', 'https')
            ->assertJsonPath('attributes.memory', 100)
            ->assertJsonPath('attributes.memory_overallocate', 10)
            ->assertJsonPath('attributes.disk', 200)
            ->assertJsonPath('attributes.disk_overallocate', 20)
            ->assertJsonPath('attributes.daemon_sftp', 1101)
            ->assertJsonPath('attributes.daemon_listen', 1102);

        $this->assertEquals($location->id, $node->refresh()->location_id);
    }
}
