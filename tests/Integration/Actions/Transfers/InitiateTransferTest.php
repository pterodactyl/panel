<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Transfers\InitiateTransferTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Transfers\InitiatesTransfers;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonTransfer;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** A node with room for the server. */
function targetNode(Server $server): Node
{
    return Node::factory()->create(['location_id' => $server->node->location_id, 'memory' => 102400, 'disk' => 102400]);
}

test('transfers started without the http api meet the same rules', function (string $case, string $field): void {
    $server = $this->createServerModel();
    $target = targetNode($server);
    $free = Allocation::factory()->for($target)->create(['server_id' => null]);
    $fake = new FakeDaemonTransfer;

    [$node, $allocationId, $additional] = match ($case) {
        'own node' => [$server->node, Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => null])->id, []],
        'missing default' => [$target, 0, []],
        'another default' => [$target, $this->createServerModel(['node_id' => $target->id])->allocation_id, []],
        'default on another node' => [$target, Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => null])->id, []],
        'assigned additional' => [$target, $free->id, [Allocation::factory()->for($target)->create(['server_id' => $server->id])->id]],
        'additional on another node' => [$target, $free->id, [Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => null])->id]],
        'missing additional' => [$target, $free->id, [$free->id, 999999]],
        'malformed additional' => [$target, $free->id, [1.5]],
        'additional not a list' => [$target, $free->id, ['selected' => $free->id]],
    };

    try {
        $this->app->make(InitiatesTransfers::class)->initiate($server, $node, $allocationId, $additional);
        $this->fail('The transfer was started.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    $fake->assertNothingHappened();
    $this->assertDatabaseMissing('server_transfers', ['server_id' => $server->id]);
    expect($free->refresh()->server_id)->toBeNull();
})->with([
    'a transfer to the server\'s own node' => ['own node', 'node_id'],
    'a default allocation that does not exist' => ['missing default', 'allocation_id'],
    'a default allocation that is another server\'s default' => ['another default', 'allocation_id'],
    'a default allocation on another node' => ['default on another node', 'allocation_id'],
    'an additional allocation that is assigned' => ['assigned additional', 'allocation_id'],
    'an additional allocation on another node' => ['additional on another node', 'allocation_id'],
    'an additional allocation that does not exist' => ['missing additional', 'allocation_additional.1'],
    'an additional allocation that is not an integer' => ['malformed additional', 'allocation_additional.0'],
    'additional allocations that are not a list' => ['additional not a list', 'allocation_additional'],
]);

test('additional allocations may be numeric strings, and every allocation is reserved', function (): void {
    $server = $this->createServerModel();
    $target = targetNode($server);
    $primary = Allocation::factory()->for($target)->create(['server_id' => null]);
    $additional = Allocation::factory()->for($target)->create(['server_id' => null]);
    $fake = new FakeDaemonTransfer;

    $transfer = $this->app->make(InitiatesTransfers::class)->initiate($server, $target, $primary->id, [(string) $additional->id]);

    $fake->assertNotified();
    expect($transfer->new_node)->toBe($target->id)
        ->and($transfer->new_allocation)->toBe($primary->id)
        ->and($transfer->new_additional_allocations)->toBe([$additional->id])
        ->and($primary->refresh()->server_id)->toBe($server->id)
        ->and($additional->refresh()->server_id)->toBe($server->id);
});
