<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Servers\BuildModificationServiceTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Contracts\Servers\UpdatesServerBuild;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
/**
 * Setup tests.
 */
beforeEach(function () {
    $this->daemonServerRepository = new FakeDaemonServer;
});
test('allocations can be modified for the server', function () {
    $server = $this->createServerModel();
    $server2 = $this->createServerModel();
    /** @var Allocation[] $allocations */
    $allocations = Allocation::factory()->times(4)->create(['node_id' => $server->node_id, 'notes' => 'Random notes']);
    $initialAllocationId = $server->allocation_id;
    $allocations[0]->update(['server_id' => $server->id, 'notes' => 'Test notes']);
    // Some additional test allocations for the other server, not the server we are attempting
    // to modify.
    $allocations[2]->update(['server_id' => $server2->id]);
    $allocations[3]->update(['server_id' => $server2->id]);
    $response = getService()->update($server, [
        // Attempt to add one new allocation, and an allocation assigned to another server. The
        // other server allocation should be ignored, and only the allocation for this server should
        // be used.
        'add_allocations' => [$allocations[2]->id, $allocations[1]->id],
        // Remove the default server allocation, ensuring that the new allocation passed through
        // in the data becomes the default allocation.
        'remove_allocations' => [$server->allocation_id, $allocations[0]->id, $allocations[3]->id],
    ]);
    $this->daemonServerRepository->assertSynced();
    expect($response)->toBeInstanceOf(Server::class);
    // Only one allocation should exist for this server now.
    expect($response->allocations)->toHaveCount(1);
    expect($response->allocation_id)->toBe($allocations[1]->id);
    expect($response->allocation->notes)->toBeNull();
    // These two allocations should not have been touched.
    $this->assertDatabaseHas('allocations', ['id' => $allocations[2]->id, 'server_id' => $server2->id]);
    $this->assertDatabaseHas('allocations', ['id' => $allocations[3]->id, 'server_id' => $server2->id]);
    // Both of these allocations should have been removed from the server, and have had their
    // notes properly reset.
    $this->assertDatabaseHas('allocations', ['id' => $initialAllocationId, 'server_id' => null, 'notes' => null]);
    $this->assertDatabaseHas('allocations', ['id' => $allocations[0]->id, 'server_id' => null, 'notes' => null]);
});
test('exception is thrown if removing the default allocation', function () {
    $server = $this->createServerModel();
    /** @var Allocation[] $allocations */
    $allocations = Allocation::factory()->times(4)->create(['node_id' => $server->node_id]);
    $allocations[0]->update(['server_id' => $server->id]);
    $this->expectException(DisplayException::class);
    $this->expectExceptionMessage('You are attempting to delete the default allocation for this server but there is no fallback allocation to use.');
    getService()->update($server, ['add_allocations' => [], 'remove_allocations' => [$server->allocation_id, $allocations[0]->id]]);
});
test('server build data is properly updated on wings', function () {
    $server = $this->createServerModel();
    $response = getService()->update($server, ['oom_disabled' => false, 'memory' => 256, 'swap' => 128, 'io' => 600, 'cpu' => 150, 'threads' => '1,2', 'disk' => 1024, 'backup_limit' => null, 'database_limit' => 10, 'allocation_limit' => 20]);
    $this->daemonServerRepository->assertSynced();
    expect($response->oom_disabled)->toBeFalse();
    expect($response->memory)->toBe(256);
    expect($response->swap)->toBe(128);
    expect($response->io)->toBe(600);
    expect($response->cpu)->toBe(150);
    expect($response->threads)->toBe('1,2');
    expect($response->disk)->toBe(1024);
    expect($response->backup_limit)->toBe(0);
    expect($response->database_limit)->toBe(10);
    expect($response->allocation_limit)->toBe(20);
});
test('connection exception is ignored when updating server settings', function () {
    $server = $this->createServerModel();
    $this->daemonServerRepository->throwable = new DaemonConnectionException(Http::failedRequest([], 200));
    $response = getService()->update($server, ['memory' => 256, 'disk' => 10240]);
    $this->daemonServerRepository->assertSynced();
    expect($response)->toBeInstanceOf(Server::class);
    expect($response->memory)->toBe(256);
    expect($response->disk)->toBe(10240);
    $this->assertDatabaseHas('servers', ['id' => $response->id, 'memory' => 256, 'disk' => 10240]);
});
test('no exception is thrown if only removing allocation', function () {
    $server = $this->createServerModel();
    /** @var Allocation $allocation */
    $allocation = Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => $server->id]);
    getService()->update($server, ['remove_allocations' => [$allocation->id]]);
    $this->daemonServerRepository->assertSynced();
    $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'server_id' => null]);
});
test('allocation in both add and remove is added', function () {
    $server = $this->createServerModel();
    /** @var Allocation $allocation */
    $allocation = Allocation::factory()->create(['node_id' => $server->node_id]);
    getService()->update($server, ['add_allocations' => [$allocation->id], 'remove_allocations' => [$allocation->id]]);
    $this->daemonServerRepository->assertSynced();
    $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'server_id' => $server->id]);
});
test('using same allocation id multiple times does not error', function () {
    $server = $this->createServerModel();
    /** @var Allocation $allocation */
    $allocation = Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => $server->id]);
    /** @var Allocation $allocation2 */
    $allocation2 = Allocation::factory()->create(['node_id' => $server->node_id]);
    getService()->update($server, ['add_allocations' => [$allocation2->id, $allocation2->id], 'remove_allocations' => [$allocation->id, $allocation->id]]);
    $this->daemonServerRepository->assertSynced();
    $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'server_id' => null]);
    $this->assertDatabaseHas('allocations', ['id' => $allocation2->id, 'server_id' => $server->id]);
});
test('post-commit sync failures propagate without rolling back committed changes', function () {
    $server = $this->createServerModel();
    /** @var Allocation $allocation */
    $allocation = Allocation::factory()->create(['node_id' => $server->node_id]);
    $this->daemonServerRepository->throwable = new DisplayException('Test');
    try {
        getService()->update($server, ['add_allocations' => [$allocation->id]]);
        $this->fail('Expected DisplayException to be thrown.');
    } catch (DisplayException) {
    }
    $this->daemonServerRepository->assertSynced();
    // The allocation attach commits before Wings is contacted, so it persists.
    $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'server_id' => $server->id]);
});
function getService(): UpdatesServerBuild
{
    return (function () {
        return $this->app->make(UpdatesServerBuild::class);
    })->call(pterodactylTestCase());
}
