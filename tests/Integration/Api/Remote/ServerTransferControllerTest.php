<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Remote\ServerTransferControllerTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
beforeEach(function () {
    $server = $this->createServerModel();
    $new = Node::factory()->for(Location::factory())->has(Allocation::factory())->create();
    $this->transfer = ServerTransfer::factory()->for($server)->create(['old_allocation' => $server->allocation_id, 'new_allocation' => $new->allocations->first()->id, 'new_node' => $new->id, 'old_node' => $server->node_id]);
});
test('success status update can be sent from new node', function () {
    $server = $this->transfer->server;
    $source = $this->transfer->oldNode;
    $url = $source->getConnectionAddress()."/api/servers/{$server->uuid}";
    Http::fake([$url => Http::response()]);
    $newNode = $this->transfer->newNode;
    $this->withHeader('Authorization', "Bearer {$newNode->daemon_token_id}.".$newNode->getDecryptedKey())->postJson("/api/remote/servers/{$server->uuid}/transfer/success")->assertNoContent();
    expect($this->transfer->refresh()->successful)->toBeTrue();
    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && $request->url() === $url
        && $request->hasHeader('Authorization', 'Bearer '.$source->getDecryptedKey()));
    Http::assertSentCount(1);
});
test('failure status update can be sent from old node', function () {
    $server = $this->transfer->server;
    $oldNode = $this->transfer->oldNode;
    $this->withHeader('Authorization', "Bearer {$oldNode->daemon_token_id}.".$oldNode->getDecryptedKey())->postJson("/api/remote/servers/{$server->uuid}/transfer/failure")->assertNoContent();
    expect($this->transfer->refresh()->successful)->toBeFalse();
});
test('failure status update can be sent from new node', function () {
    $server = $this->transfer->server;
    $newNode = $this->transfer->newNode;
    $this->withHeader('Authorization', "Bearer {$newNode->daemon_token_id}.".$newNode->getDecryptedKey())->postJson("/api/remote/servers/{$server->uuid}/transfer/failure")->assertNoContent();
    expect($this->transfer->refresh()->successful)->toBeFalse();
});
test('success status update cannot be sent from old node', function () {
    $server = $this->transfer->server;
    $oldNode = $this->transfer->oldNode;
    $this->withHeader('Authorization', "Bearer {$oldNode->daemon_token_id}.".$oldNode->getDecryptedKey())->postJson("/api/remote/servers/{$server->uuid}/transfer/success")->assertForbidden()->assertJsonPath('errors.0.code', 'HttpForbiddenException')->assertJsonPath('errors.0.detail', 'Requesting node does not have permission to access this server.');
    expect($this->transfer->refresh()->successful)->toBeNull();
});
test('success status update cannot be sent from unauthorized node', function () {
    $server = $this->transfer->server;
    $node = Node::factory()->for(Location::factory())->create();
    $this->withHeader('Authorization', "Bearer {$node->daemon_token_id}.".$node->getDecryptedKey())->postJson("/api/remote/servers/{$server->uuid}/transfer/success")->assertForbidden()->assertJsonPath('errors.0.code', 'HttpForbiddenException')->assertJsonPath('errors.0.detail', 'Requesting node does not have permission to access this server.');
    expect($this->transfer->refresh()->successful)->toBeNull();
});
test('failure status update cannot be sent from unauthorized node', function () {
    $server = $this->transfer->server;
    $node = Node::factory()->for(Location::factory())->create();
    $this->withHeader('Authorization', "Bearer {$node->daemon_token_id}.".$node->getDecryptedKey())->postJson("/api/remote/servers/{$server->uuid}/transfer/failure")->assertForbidden()->assertJsonPath('errors.0.code', 'HttpForbiddenException')->assertJsonPath('errors.0.detail', 'Requesting node does not have permission to access this server.');
    expect($this->transfer->refresh()->successful)->toBeNull();
});

test('transfer results are reported to operation listeners', function (string $result, bool $successful) {
    Event::fake([OperationCompleted::class]);
    $server = $this->transfer->server;
    Http::fake([$this->transfer->oldNode->getConnectionAddress()."/api/servers/{$server->uuid}" => Http::response()]);
    $newNode = $this->transfer->newNode;

    $this->withHeader('Authorization', "Bearer {$newNode->daemon_token_id}.".$newNode->getDecryptedKey())->postJson("/api/remote/servers/{$server->uuid}/transfer/{$result}")->assertNoContent();

    Event::assertDispatchedTimes(OperationCompleted::class, 1);
    Event::assertDispatched(OperationCompleted::class, fn (OperationCompleted $event): bool => $event->operation === 'transfer'
        && $event->successful === $successful
        && $event->serverUuid === $server->uuid
        && $event->resourceUuid === $server->uuid);
})->with([['success', true], ['failure', false]]);
