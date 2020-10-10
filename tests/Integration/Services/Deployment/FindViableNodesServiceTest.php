<?php

namespace Pterodactyl\Tests\Integration\Services\Deployment;

use Pterodactyl\Models\Node;
use InvalidArgumentException;
use Pterodactyl\Models\Location;
use Illuminate\Support\Collection;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Services\Deployment\FindViableNodesService;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;

class FindViableNodesServiceTest extends IntegrationTestCase
{
    public function testExceptionIsThrownIfNoDiskSpaceHasBeenSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Disk space must be an int, got NULL');

        $this->getService()->handle();
    }

    public function testExceptionIsThrownIfNoMemoryHasBeenSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Memory usage must be an int, got NULL');

        $this->getService()->setDisk(10)->handle();
    }

    public function testExpectedNodeIsReturnedForLocation()
    {
        /** @var \Pterodactyl\Models\Location[] $locations */
        $locations = factory(Location::class)->times(2)->create();

        /** @var \Pterodactyl\Models\Node[] $nodes */
        $nodes = [
            // This node should never be returned.
            factory(Node::class)->create([
                'location_id' => $locations[0]->id,
                'memory' => 10240,
                'disk' => 1024 * 100,
            ]),
            factory(Node::class)->create([
                'location_id' => $locations[1]->id,
                'memory' => 1024,
                'disk' => 10240,
                'disk_overallocate' => 10,
            ]),
            factory(Node::class)->create([
                'location_id' => $locations[1]->id,
                'memory' => 1024 * 4,
                'memory_overallocate' => 50,
                'disk' => 102400,
            ]),
        ];

        $base = function () use ($locations) {
            return $this->getService()->setLocations([ $locations[1]->id ])->setDisk(512);
        };

        $response = $base()->setMemory(512)->handle();
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertFalse($response->isEmpty());
        $this->assertSame(2, $response->count());
        $this->assertSame(2, $response->where('location_id', $locations[1]->id)->count());

        $response = $base()->setMemory(2048)->handle();
        $this->assertSame(1, $response->count());
        $this->assertSame($nodes[2]->id, $response[0]->id);

        $response = $base()->setDisk(20480)->setMemory(256)->handle();
        $this->assertSame(1, $response->count());
        $this->assertSame($nodes[2]->id, $response[0]->id);

        $response = $base()->setDisk(11263)->setMemory(256)->handle();
        $this->assertSame(2, $response->count());

        $servers = Collection::make([
            $this->createServerModel(['node_id' => $nodes[1]->id, 'disk' => 5120]),
            $this->createServerModel(['node_id' => $nodes[1]->id, 'disk' => 5120]),
        ]);

        $response = $base()->setDisk(1024)->setMemory(256)->handle();
        $this->assertSame(1, $response->count());
        $this->assertSame($nodes[2]->id, $response[0]->id);
        $servers->each->delete();

        $this->expectException(NoViableNodeException::class);
        $base()->setMemory(10000)->handle();

        Collection::make([
            $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]),
            $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]),
            $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]),
            $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]),
        ]);

        $response = $base()->setMemory(500)->handle();
        $this->assertSame(2, $response->count());
        $this->assertSame(2, $response->where('location_id', $locations[1]->id)->count());

        $response = $base()->setMemory(512)->handle();
        $this->assertSame(1, $response->count());
        $this->assertSame($nodes[1]->id, $response[0]->id);
    }

    /**
     * @return \Pterodactyl\Services\Deployment\FindViableNodesService
     */
    private function getService()
    {
        return $this->app->make(FindViableNodesService::class);
    }
}
