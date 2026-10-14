<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Servers\UpdateServerBuildTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
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
test('multiple allocations can be added at once', function () {
    $server = $this->createServerModel();
    /** @var Allocation[] $allocations */
    $allocations = Allocation::factory()->times(3)->create(['node_id' => $server->node_id]);
    $response = getService()->update($server, ['add_allocations' => $allocations->pluck('id')->all()]);
    $this->daemonServerRepository->assertSynced();
    expect($response->allocations)->toHaveCount(4);
    foreach ($allocations as $allocation) {
        $this->assertDatabaseHas('allocations', ['id' => $allocation->id, 'server_id' => $server->id]);
    }
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
test('builds updated without the http api meet the same rules', function (array $input, string $field) {
    $server = $this->createServerModel()->refresh();
    $before = $server->getAttributes();

    try {
        getService()->update($server, $input);
        $this->fail('The build was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($server->fresh()->getAttributes())->toBe($before)
        ->and($this->daemonServerRepository->callsFor('sync'))->toBe([]);
})->with([
    'a missing default allocation' => [['allocation_id' => null], 'allocation_id'],
    'a default allocation that does not exist' => [['allocation_id' => 0], 'allocation_id'],
    'an oom flag that is not a boolean' => [['oom_disabled' => 'yes'], 'oom_disabled'],
    'a missing memory limit' => [['memory' => null], 'memory'],
    'memory below zero' => [['memory' => -1], 'memory'],
    'swap below unlimited' => [['swap' => -2], 'swap'],
    'io outside its range' => [['io' => 5], 'io'],
    'a cpu limit that is not an integer' => [['cpu' => 'all'], 'cpu'],
    'cpu threads that are not a pattern' => [['threads' => '0-3;'], 'threads'],
    'a fractional disk size' => [['disk' => 1024.5], 'disk'],
    'allocations to add that are not a list' => [['add_allocations' => 'all'], 'add_allocations'],
    'an allocation to remove that is not an integer' => [['remove_allocations' => ['first']], 'remove_allocations.0'],
    'a negative database limit' => [['database_limit' => -1], 'database_limit'],
    'an allocation limit that is not an integer' => [['allocation_limit' => '1.5'], 'allocation_limit'],
    'a negative backup limit' => [['backup_limit' => -1], 'backup_limit'],
]);
test('a default allocation that is another server\'s default is refused', function () {
    $server = $this->createServerModel();
    $other = $this->createServerModel(['node_id' => $server->node_id]);

    try {
        getService()->update($server, ['allocation_id' => $other->allocation_id]);
        $this->fail('The build was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe(['allocation_id']);
    }

    // The server's own default allocation is not a clash.
    expect(getService()->update($server, ['allocation_id' => $server->allocation_id])->allocation_id)->toBe($server->allocation_id);
});
test('a build update changes only the fields it is given and resets the feature limits', function () {
    $server = $this->createServerModel(['oom_disabled' => true, 'memory' => 512, 'swap' => 0, 'io' => 500, 'cpu' => 100, 'threads' => '0-1', 'disk' => 1024, 'database_limit' => 2, 'allocation_limit' => 3, 'backup_limit' => 4]);

    $response = getService()->update($server, ['memory' => 2048]);

    $this->daemonServerRepository->assertSynced();
    expect($response->memory)->toBe(2048)
        ->and($response->oom_disabled)->toBeTrue()
        ->and($response->swap)->toBe(0)
        ->and($response->io)->toBe(500)
        ->and($response->cpu)->toBe(100)
        ->and($response->threads)->toBe('0-1')
        ->and($response->disk)->toBe(1024)
        ->and($response->allocation_id)->toBe($server->allocation_id)
        // Left out, the database and allocation limits are cleared and the backup limit is 0.
        ->and($response->database_limit)->toBeNull()
        ->and($response->allocation_limit)->toBeNull()
        ->and($response->backup_limit)->toBe(0);
});
test('build values are coerced the way laravel validates them', function (int|string $flag, bool $expected) {
    $server = $this->createServerModel();
    $spare = Allocation::factory()->create(['node_id' => $server->node_id]);

    $response = getService()->update($server, [
        'allocation_id' => (string) $server->allocation_id,
        'oom_disabled' => $flag,
        'memory' => '512',
        'swap' => '-1',
        'io' => '500',
        'cpu' => '100',
        'threads' => 3,
        'disk' => '1024',
        'add_allocations' => [(string) $spare->id],
        'database_limit' => '1',
        'allocation_limit' => 2,
        'backup_limit' => '3',
    ]);

    expect($response->allocation_id)->toBe($server->allocation_id)
        ->and($response->oom_disabled)->toBe($expected)
        ->and($response->memory)->toBe(512)
        ->and($response->swap)->toBe(-1)
        ->and($response->io)->toBe(500)
        ->and($response->cpu)->toBe(100)
        ->and($response->threads)->toBe('3')
        ->and($response->disk)->toBe(1024)
        ->and($response->database_limit)->toBe(1)
        ->and($response->allocation_limit)->toBe(2)
        ->and($response->backup_limit)->toBe(3)
        ->and($spare->refresh()->server_id)->toBe($server->id);
})->with([[1, true], ['1', true], [0, false], ['0', false]]);
function getService(): UpdatesServerBuild
{
    return (function () {
        return $this->app->make(UpdatesServerBuild::class);
    })->call(pterodactylTestCase());
}
