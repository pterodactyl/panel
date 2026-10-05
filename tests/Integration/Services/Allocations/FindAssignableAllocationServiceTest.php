<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Allocations\FindAssignableAllocationServiceTest;

use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Pterodactyl\Actions\Allocations\AssignAvailableAllocation;
use Pterodactyl\Exceptions\Service\Allocation\AutoAllocationNotEnabledException;
use Pterodactyl\Exceptions\Service\Allocation\NoAutoAllocationSpaceAvailableException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
/**
 * Setup tests.
 */
beforeEach(function () {
    config()->set('pterodactyl.client_features.allocations.enabled', true);
    config()->set('pterodactyl.client_features.allocations.range_start', 0);
    config()->set('pterodactyl.client_features.allocations.range_end', 0);
});
test('existing allocation is preferred', function () {
    $server = $this->createServerModel();
    $created = Allocation::factory()->create(['node_id' => $server->node_id, 'ip' => $server->allocation->ip]);
    $response = getAction()->assignAvailable($server);
    expect($response->id)->toBe($created->id);
    expect($response->ip)->toBe($server->allocation->ip);
    expect($response->node_id)->toBe($server->node_id);
    expect($response->server_id)->toBe($server->id);
    $this->assertNotSame($server->allocation_id, $response->id);
});
test('new allocation is created if one is not found', function () {
    $server = $this->createServerModel();
    config()->set('pterodactyl.client_features.allocations.range_start', 5000);
    config()->set('pterodactyl.client_features.allocations.range_end', 5005);
    $response = getAction()->assignAvailable($server);
    expect($response->server_id)->toBe($server->id);
    expect($response->ip)->toBe($server->allocation->ip);
    expect($response->node_id)->toBe($server->node_id);
    $this->assertNotSame($server->allocation_id, $response->id);
    expect($response->port >= 5000 && $response->port <= 5005)->toBeTrue();
});
test('numeric string ranges are normalized', function () {
    $server = $this->createServerModel();
    config()->set('pterodactyl.client_features.allocations.range_start', '5000');
    config()->set('pterodactyl.client_features.allocations.range_end', '5001');
    $response = getAction()->assignAvailable($server);
    expect([5000, 5001])->toContain($response->port);
});
test('only port not in use is created', function () {
    $server = $this->createServerModel();
    $server2 = $this->createServerModel(['node_id' => $server->node_id]);
    config()->set('pterodactyl.client_features.allocations.range_start', 5000);
    config()->set('pterodactyl.client_features.allocations.range_end', 5001);
    Allocation::factory()->create(['server_id' => $server2->id, 'node_id' => $server->node_id, 'ip' => $server->allocation->ip, 'port' => 5000]);
    $response = getAction()->assignAvailable($server);
    expect($response->port)->toBe(5001);
});
test('exception is thrown if no more allocations can be created in range', function () {
    $server = $this->createServerModel();
    $server2 = $this->createServerModel(['node_id' => $server->node_id]);
    config()->set('pterodactyl.client_features.allocations.range_start', 5000);
    config()->set('pterodactyl.client_features.allocations.range_end', 5005);
    for ($i = 5000; $i <= 5005; $i++) {
        Allocation::factory()->create(['ip' => $server->allocation->ip, 'port' => $i, 'node_id' => $server->node_id, 'server_id' => $server2->id]);
    }
    $this->expectException(NoAutoAllocationSpaceAvailableException::class);
    $this->expectExceptionMessage('Cannot assign additional allocation: no more space available on node.');
    getAction()->assignAvailable($server);
});
test('exception is thrown if only free port is on a different ip', function () {
    $server = $this->createServerModel();
    Allocation::factory()->times(5)->create(['node_id' => $server->node_id]);
    $this->expectException(NoAutoAllocationSpaceAvailableException::class);
    $this->expectExceptionMessage('Cannot assign additional allocation: no more space available on node.');
    getAction()->assignAvailable($server);
});
test('exception is thrown if start or end range is not defined', function () {
    $server = $this->createServerModel();
    $this->expectException(NoAutoAllocationSpaceAvailableException::class);
    $this->expectExceptionMessage('Cannot assign additional allocation: no more space available on node.');
    getAction()->assignAvailable($server);
});
test('exception is thrown if start or end range is not numeric', function () {
    $server = $this->createServerModel();
    config()->set('pterodactyl.client_features.allocations.range_start', 'hodor');
    config()->set('pterodactyl.client_features.allocations.range_end', 10);
    try {
        getAction()->assignAvailable($server);
        $this->fail('This assertion should not be reached.');
    } catch (Exception $exception) {
        expect($exception)->toBeInstanceOf(InvalidArgumentException::class);
        expect($exception->getMessage())->toBe('Expected allocation range start to be an integer-like value.');
    }
    config()->set('pterodactyl.client_features.allocations.range_start', 10);
    config()->set('pterodactyl.client_features.allocations.range_end', 'hodor');
    try {
        getAction()->assignAvailable($server);
        $this->fail('This assertion should not be reached.');
    } catch (Exception $exception) {
        expect($exception)->toBeInstanceOf(InvalidArgumentException::class);
        expect($exception->getMessage())->toBe('Expected allocation range end to be an integer-like value.');
    }
});
test('exception is thrown if feature is not enabled', function () {
    config()->set('pterodactyl.client_features.allocations.enabled', false);
    $server = $this->createServerModel();
    $this->expectException(AutoAllocationNotEnabledException::class);
    getAction()->assignAvailable($server);
});
function getAction(): AssignAvailableAllocation
{
    return (function () {
        return $this->app->make(AssignAvailableAllocation::class);
    })->call(pterodactylTestCase());
}
