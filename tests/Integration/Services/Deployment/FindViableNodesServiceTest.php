<?php

namespace Pterodactyl\Tests\Integration\Services\Deployment;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Location;
use Illuminate\Support\Collection;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Services\Deployment\FindViableNodesService;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;

class FindViableNodesServiceTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Database::query()->delete();
        Server::query()->delete();
        Node::query()->delete();
    }

    public function testExceptionIsThrownIfNoDiskSpaceHasBeenSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Disk space must be an int, got NULL');

        $this->getService()->handle();
    }

    public function testExceptionIsThrownIfNoMemoryHasBeenSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Memory usage must be an int, got NULL');

        $this->getService()->setDisk(10)->handle();
    }

    /**
     * Ensure that errors are not thrown back when passing in expected values.
     *
     * @see https://github.com/pterodactyl/panel/issues/2529
     */
    public function testNoExceptionIsThrownIfStringifiedIntegersArePassedForLocations()
    {
        $this->getService()->setLocations([1, 2, 3]);
        $this->getService()->setLocations(['1', '2', '3']);
        $this->getService()->setLocations(['1', 2, 3]);

        try {
            $this->getService()->setLocations(['a']);
            $this->fail('This expectation should not be called.');
        } catch (\Exception $exception) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
            $this->assertSame('An array of location IDs should be provided when calling setLocations.', $exception->getMessage());
        }

        try {
            $this->getService()->setLocations(['1.2', '1', 2]);
            $this->fail('This expectation should not be called.');
        } catch (\Exception $exception) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
            $this->assertSame('An array of location IDs should be provided when calling setLocations.', $exception->getMessage());
        }
    }

    public function testExpectedNodeIsReturnedForLocation()
    {
        /** @var \Pterodactyl\Models\Location[] $locations */
        $locations = Location::factory()->times(2)->create();

        /** @var \Pterodactyl\Models\Node[] $nodes */
        $nodes = [
            // This node should never be returned once we've completed the initial test which
            // runs without a location filter.
            Node::factory()->create([
                'location_id' => $locations[0]->id,
                'memory' => 2048,
                'disk' => 1024 * 100,
            ]),
            Node::factory()->create([
                'location_id' => $locations[1]->id,
                'memory' => 1024,
                'disk' => 10240,
                'disk_overallocate' => 10,
            ]),
            Node::factory()->create([
                'location_id' => $locations[1]->id,
                'memory' => 1024 * 4,
                'memory_overallocate' => 50,
                'disk' => 102400,
            ]),
        ];

        // Expect that all the nodes are returned as we're under all of their limits
        // and there is no location filter being provided.
        $response = $this->getService()->setDisk(512)->setMemory(512)->handle();
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(3, $response);
        $this->assertInstanceOf(Node::class, $response[0]);

        // Expect that only the last node is returned because it is the only one with enough
        // memory available to this instance.
        $response = $this->getService()->setDisk(512)->setMemory(2049)->handle();
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(1, $response);
        $this->assertSame($nodes[2]->id, $response[0]->id);

        // Helper, I am lazy.
        $base = function () use ($locations) {
            return $this->getService()->setLocations([$locations[1]->id])->setDisk(512);
        };

        // Expect that we can create this server on either node since the disk and memory
        // limits are below the allowed amount.
        $response = $base()->setMemory(512)->handle();
        $this->assertCount(2, $response);
        $this->assertSame(2, $response->where('location_id', $locations[1]->id)->count());

        // Expect that we can only create this server on the second node since the memory
        // allocated is over the amount of memory available to the first node.
        $response = $base()->setMemory(2048)->handle();
        $this->assertCount(1, $response);
        $this->assertSame($nodes[2]->id, $response[0]->id);

        // Expect that we can only create this server on the second node since the disk
        // allocated is over the limit assigned to the first node (even with the overallocate).
        $response = $base()->setDisk(20480)->setMemory(256)->handle();
        $this->assertCount(1, $response);
        $this->assertSame($nodes[2]->id, $response[0]->id);

        // Expect that we could create the server on either node since the disk allocated is
        // right at the limit for Node 1 when the overallocate value is included in the calc.
        $response = $base()->setDisk(11264)->setMemory(256)->handle();
        $this->assertCount(2, $response);

        // Create two servers on the first node so that the disk space used is equal to the
        // base amount available to the node (without overallocation included).
        $servers = Collection::make([
            $this->createServerModel(['node_id' => $nodes[1]->id, 'disk' => 5120]),
            $this->createServerModel(['node_id' => $nodes[1]->id, 'disk' => 5120]),
        ]);

        // Expect that we cannot create a server with a 1GB disk on the first node since there
        // is not enough space (even with the overallocate) available to the node.
        $response = $base()->setDisk(1024)->setMemory(256)->handle();
        $this->assertCount(1, $response);
        $this->assertSame($nodes[2]->id, $response[0]->id);

        // Cleanup servers since we need to test some other stuff with memory here.
        $servers->each->delete();

        // Expect that no viable node can be found when the memory limit for the given instance
        // is greater than either node can support, even with the overallocation limits taken
        // into account.
        $this->expectException(NoViableNodeException::class);
        $base()->setMemory(10000)->handle();

        // Create four servers so that the memory used for the second node is equal to the total
        // limit for that node (pre-overallocate calculation).
        Collection::make([
            $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]),
            $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]),
            $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]),
            $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]),
        ]);

        // Expect that either node can support this server when we account for the overallocate
        // value of the second node.
        $response = $base()->setMemory(500)->handle();
        $this->assertCount(2, $response);
        $this->assertSame(2, $response->where('location_id', $locations[1]->id)->count());

        // Expect that only the first node can support this server when we go over the remaining
        // memory for the second nodes overallocate calculation.
        $response = $base()->setMemory(640)->handle();
        $this->assertCount(1, $response);
        $this->assertSame($nodes[1]->id, $response[0]->id);
    }

    private function getService(): FindViableNodesService
    {
        return $this->app->make(FindViableNodesService::class);
    }
}
