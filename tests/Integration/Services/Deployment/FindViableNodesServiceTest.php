<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Deployment\FindViableNodesServiceTest;

use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Deployment\FindViableNodesService;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
beforeEach(function () {
    Database::query()->delete();
    Server::query()->delete();
    Node::query()->delete();
});
test('exception is thrown if no disk space has been set', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Disk space must be an int, got null');
    getService()->handle();
});
test('exception is thrown if no memory has been set', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Memory usage must be an int, got null');
    getService()->setDisk(10)->handle();
});
test('no exception is thrown if stringified integers are passed for locations', function () {
    getService()->setLocations([1, 2, 3]);
    getService()->setLocations(['1', '2', '3']);
    getService()->setLocations(['1', 2, 3]);
    try {
        getService()->setLocations(['a']);
        $this->fail('This expectation should not be called.');
    } catch (Exception $exception) {
        expect($exception)->toBeInstanceOf(InvalidArgumentException::class);
        expect($exception->getMessage())->toBe('An array of location IDs should be provided when calling setLocations.');
    }
    try {
        getService()->setLocations(['1.2', '1', 2]);
        $this->fail('This expectation should not be called.');
    } catch (Exception $exception) {
        expect($exception)->toBeInstanceOf(InvalidArgumentException::class);
        expect($exception->getMessage())->toBe('An array of location IDs should be provided when calling setLocations.');
    }
});
test('expected node is returned for location', function () {
    /** @var Location[] $locations */
    $locations = Location::factory()->times(2)->create();
    /** @var Node[] $nodes */
    $nodes = [
        // This node should never be returned once we've completed the initial test which
        // runs without a location filter.
        Node::factory()->create(['location_id' => $locations[0]->id, 'memory' => 2048, 'disk' => 1024 * 100]),
        Node::factory()->create(['location_id' => $locations[1]->id, 'memory' => 1024, 'disk' => 10240, 'disk_overallocate' => 10]),
        Node::factory()->create(['location_id' => $locations[1]->id, 'memory' => 1024 * 4, 'memory_overallocate' => 50, 'disk' => 102400]),
    ];
    // Expect that all the nodes are returned as we're under all of their limits
    // and there is no location filter being provided.
    $response = getService()->setDisk(512)->setMemory(512)->handle();
    expect($response)->toBeInstanceOf(Collection::class);
    expect($response)->toHaveCount(3);
    expect($response[0])->toBeInstanceOf(Node::class);
    // Expect that only the last node is returned because it is the only one with enough
    // memory available to this instance.
    $response = getService()->setDisk(512)->setMemory(2049)->handle();
    expect($response)->toBeInstanceOf(Collection::class);
    expect($response)->toHaveCount(1);
    expect($response[0]->id)->toBe($nodes[2]->id);
    // Helper, I am lazy.
    $base = function () use ($locations) {
        return getService()->setLocations([$locations[1]->id])->setDisk(512);
    };
    // Expect that we can create this server on either node since the disk and memory
    // limits are below the allowed amount.
    $response = $base()->setMemory(512)->handle();
    expect($response)->toHaveCount(2);
    expect($response->where('location_id', $locations[1]->id)->count())->toBe(2);
    // Expect that we can only create this server on the second node since the memory
    // allocated is over the amount of memory available to the first node.
    $response = $base()->setMemory(2048)->handle();
    expect($response)->toHaveCount(1);
    expect($response[0]->id)->toBe($nodes[2]->id);
    // Expect that we can only create this server on the second node since the disk
    // allocated is over the limit assigned to the first node (even with the overallocate).
    $response = $base()->setDisk(20480)->setMemory(256)->handle();
    expect($response)->toHaveCount(1);
    expect($response[0]->id)->toBe($nodes[2]->id);
    // Expect that we could create the server on either node since the disk allocated is
    // right at the limit for Node 1 when the overallocate value is included in the calc.
    $response = $base()->setDisk(11264)->setMemory(256)->handle();
    expect($response)->toHaveCount(2);
    // Create two servers on the first node so that the disk space used is equal to the
    // base amount available to the node (without overallocation included).
    $servers = Collection::make([$this->createServerModel(['node_id' => $nodes[1]->id, 'disk' => 5120]), $this->createServerModel(['node_id' => $nodes[1]->id, 'disk' => 5120])]);
    // Expect that we cannot create a server with a 1GB disk on the first node since there
    // is not enough space (even with the overallocate) available to the node.
    $response = $base()->setDisk(1024)->setMemory(256)->handle();
    expect($response)->toHaveCount(1);
    expect($response[0]->id)->toBe($nodes[2]->id);
    // Cleanup servers since we need to test some other stuff with memory here.
    $servers->each->delete();
    // Expect that no viable node can be found when the memory limit for the given instance
    // is greater than either node can support, even with the overallocation limits taken
    // into account.
    $this->expectException(NoViableNodeException::class);
    $base()->setMemory(10000)->handle();
    // Create four servers so that the memory used for the second node is equal to the total
    // limit for that node (pre-overallocate calculation).
    Collection::make([$this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]), $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]), $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024]), $this->createServerModel(['node_id' => $nodes[2]->id, 'memory' => 1024])]);
    // Expect that either node can support this server when we account for the overallocate
    // value of the second node.
    $response = $base()->setMemory(500)->handle();
    expect($response)->toHaveCount(2);
    expect($response->where('location_id', $locations[1]->id)->count())->toBe(2);
    // Expect that only the first node can support this server when we go over the remaining
    // memory for the second nodes overallocate calculation.
    $response = $base()->setMemory(640)->handle();
    expect($response)->toHaveCount(1);
    expect($response[0]->id)->toBe($nodes[1]->id);
});
function getService(): FindViableNodesService
{
    return (function () {
        return $this->app->make(FindViableNodesService::class);
    })->call(pterodactylTestCase());
}
