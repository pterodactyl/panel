<?php

namespace Pterodactyl\Tests\Integration\Services\Servers;

use Mockery\MockInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Services\Servers\BuildModificationService;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class BuildModificationServiceTest extends IntegrationTestCase
{
    private MockInterface $daemonServerRepository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->daemonServerRepository = $this->mock(DaemonServerRepository::class);
    }

    /**
     * Test that allocations can be added and removed from a server. Only the allocations on the
     * current node and belonging to this server should be modified.
     */
    public function testAllocationsCanBeModifiedForTheServer()
    {
        $server = $this->createServerModel();
        $server2 = $this->createServerModel();

        /** @var \Pterodactyl\Models\Allocation[] $allocations */
        $allocations = Allocation::factory()->times(4)->create(['node_id' => $server->node_id, 'notes' => 'Random notes']);

        $initialAllocationId = $server->allocation_id;
        $allocations[0]->update(['server_id' => $server->id, 'notes' => 'Test notes']);

        // Some additional test allocations for the other server, not the server we are attempting
        // to modify.
        $allocations[2]->update(['server_id' => $server2->id]);
        $allocations[3]->update(['server_id' => $server2->id]);

        $this->daemonServerRepository->expects('setServer->sync')->andReturnUndefined();

        $response = $this->getService()->handle($server, [
            // Attempt to add one new allocation, and an allocation assigned to another server. The
            // other server allocation should be ignored, and only the allocation for this server should
            // be used.
            'add_allocations' => [$allocations[2]->id, $allocations[1]->id],
            // Remove the default server allocation, ensuring that the new allocation passed through
            // in the data becomes the default allocation.
            'remove_allocations' => [$server->allocation_id, $allocations[0]->id, $allocations[3]->id],
        ]);

        $this->assertInstanceOf(Server::class, $response);

        // Only one allocation should exist for this server now.
        $this->assertCount(1, $response->allocations);
        $this->assertSame($allocations[1]->id, $response->allocation_id);
        $this->assertNull($response->allocation->notes);

        // These two allocations should not have been touched.
        $this->assertDatabaseHas('allocations', ['id' => $allocations[2]->id, 'server_id' => $server2->id]);
        $this->assertDatabaseHas('allocations', ['id' => $allocations[3]->id, 'server_id' => $server2->id]);

        // Both of these allocations should have been removed from the server, and have had their
        // notes properly reset.
        $this->assertDatabaseHas('allocations', ['id' => $initialAllocationId, 'server_id' => null, 'notes' => null]);
        $this->assertDatabaseHas('allocations', ['id' => $allocations[0]->id, 'server_id' => null, 'notes' => null]);
    }

    /**
     * Test that an exception is thrown if removing the default allocation without also assigning
     * new allocations to the server.
     */
    public function testExceptionIsThrownIfRemovingTheDefaultAllocation()
    {
        $server = $this->createServerModel();
        /** @var \Pterodactyl\Models\Allocation[] $allocations */
        $allocations = Allocation::factory()->times(4)->create(['node_id' => $server->node_id]);

        $allocations[0]->update(['server_id' => $server->id]);

        $this->expectException(DisplayException::class);
        $this->expectExceptionMessage('You are attempting to delete the default allocation for this server but there is no fallback allocation to use.');

        $this->getService()->handle($server, [
            'add_allocations' => [],
            'remove_allocations' => [$server->allocation_id, $allocations[0]->id],
        ]);
    }

    /**
     * Test that the build data for the server is properly passed along to the Wings instance so that
     * the server data is updated in realtime. This test also ensures that only certain fields get updated
     * for the server, and not just any arbitrary field.
     */
    public function testServerBuildDataIsProperlyUpdatedOnWings()
    {
        $server = $this->createServerModel();

        $this->daemonServerRepository->expects('setServer')->with(\Mockery::on(function (Server $s) use ($server) {
            return $s->id === $server->id;
        }))->andReturnSelf();

        $this->daemonServerRepository->expects('sync')->withNoArgs()->andReturnUndefined();

        $response = $this->getService()->handle($server, [
            'oom_killer' => true,
            'memory' => 256,
            'swap' => 128,
            'io' => 600,
            'cpu' => 150,
            'threads' => '1,2',
            'disk' => 1024,
            'backup_limit' => null,
            'database_limit' => 10,
            'allocation_limit' => 20,
        ]);

        $this->assertTrue($response->oom_killer);
        $this->assertSame(256, $response->memory);
        $this->assertSame(128, $response->swap);
        $this->assertSame(600, $response->io);
        $this->assertSame(150, $response->cpu);
        $this->assertSame('1,2', $response->threads);
        $this->assertSame(1024, $response->disk);
        $this->assertSame(0, $response->backup_limit);
        $this->assertSame(10, $response->database_limit);
        $this->assertSame(20, $response->allocation_limit);
    }

    /**
     * Test that an exception when connecting to the Wings instance is properly ignored
     * when making updates. This allows for a server to be modified even when the Wings
     * node is offline.
     */
    public function testConnectionExceptionIsIgnoredWhenUpdatingServerSettings()
    {
        $server = $this->createServerModel();

        $this->daemonServerRepository->expects('setServer->sync')->andThrows(
            new DaemonConnectionException(
                new RequestException('Bad request', new Request('GET', '/test'), new Response())
            )
        );

        $response = $this->getService()->handle($server, ['memory' => 256, 'disk' => 10240]);

        $this->assertInstanceOf(Server::class, $response);
        $this->assertSame(256, $response->memory);
        $this->assertSame(10240, $response->disk);

        $this->assertDatabaseHas('servers', ['id' => $response->id, 'memory' => 256, 'disk' => 10240]);
    }

    /**
     * Test that no exception is thrown if we are only removing an allocation.
     */
    public function testNoExceptionIsThrownIfOnlyRemovingAllocation()
    {
        $server = $this->createServerModel();
        /** @var \Pterodactyl\Models\Allocation $allocation */
        $allocation = Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => $server->id]);

        $this->daemonServerRepository->expects('setServer->sync')->andReturnUndefined();

        $this->getService()->handle($server, [
            'remove_allocations' => [$allocation->id],
        ]);

        $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'server_id' => null]);
    }

    /**
     * Test that allocations in both the add and remove arrays are only added, and not removed.
     * This scenario wouldn't really happen in the UI, but it is possible to perform via the API,
     * so we want to make sure that the logic being used doesn't break if the allocation exists
     * in both arrays.
     *
     * We'll default to adding the allocation in this case.
     */
    public function testAllocationInBothAddAndRemoveIsAdded()
    {
        $server = $this->createServerModel();
        /** @var \Pterodactyl\Models\Allocation $allocation */
        $allocation = Allocation::factory()->create(['node_id' => $server->node_id]);

        $this->daemonServerRepository->expects('setServer->sync')->andReturnUndefined();

        $this->getService()->handle($server, [
            'add_allocations' => [$allocation->id],
            'remove_allocations' => [$allocation->id],
        ]);

        $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'server_id' => $server->id]);
    }

    /**
     * Test that using the same allocation ID multiple times in the array does not cause an error.
     */
    public function testUsingSameAllocationIdMultipleTimesDoesNotError()
    {
        $server = $this->createServerModel();
        /** @var \Pterodactyl\Models\Allocation $allocation */
        $allocation = Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => $server->id]);
        /** @var \Pterodactyl\Models\Allocation $allocation2 */
        $allocation2 = Allocation::factory()->create(['node_id' => $server->node_id]);

        $this->daemonServerRepository->expects('setServer->sync')->andReturnUndefined();

        $this->getService()->handle($server, [
            'add_allocations' => [$allocation2->id, $allocation2->id],
            'remove_allocations' => [$allocation->id, $allocation->id],
        ]);

        $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'server_id' => null]);
        $this->assertDatabaseHas('allocations', ['id' => $allocation2->id, 'server_id' => $server->id]);
    }

    /**
     * Test that any changes we made to the server or allocations are rolled back if there is an
     * exception while performing any action. This is different from the connection exception
     * test which should properly ignore connection issues. We want any other type of exception
     * to properly be thrown back to the caller.
     */
    public function testThatUpdatesAreRolledBackIfExceptionIsEncountered()
    {
        $server = $this->createServerModel();
        /** @var \Pterodactyl\Models\Allocation $allocation */
        $allocation = Allocation::factory()->create(['node_id' => $server->node_id]);

        $this->daemonServerRepository->expects('setServer->sync')->andThrows(new DisplayException('Test'));

        $this->expectException(DisplayException::class);

        $this->getService()->handle($server, ['add_allocations' => [$allocation->id]]);

        $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'server_id' => null]);
    }

    private function getService(): BuildModificationService
    {
        return $this->app->make(BuildModificationService::class);
    }
}
