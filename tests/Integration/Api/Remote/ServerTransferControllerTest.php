<?php

namespace Pterodactyl\Tests\Integration\Api\Remote;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

class ServerTransferControllerTest extends IntegrationTestCase
{
    protected ServerTransfer $transfer;

    public function setup(): void
    {
        parent::setUp();

        $server = $this->createServerModel();

        $new = Node::factory()
            ->for(Location::factory())
            ->has(Allocation::factory())
            ->create();

        $this->transfer = ServerTransfer::factory()->for($server)->create([
            'old_allocation' => $server->allocation_id,
            'new_allocation' => $new->allocations->first()->id,
            'new_node' => $new->id,
            'old_node' => $server->node_id,
        ]);
    }

    public function testSuccessStatusUpdateCanBeSentFromNewNode(): void
    {
        $server = $this->transfer->server;
        $newNode = $this->transfer->newNode;

        $this
            ->withHeader('Authorization', "Bearer $newNode->daemon_token_id." . $newNode->getDecryptedKey())
            ->postJson("/api/remote/servers/{$server->uuid}/transfer/success")
            ->assertNoContent();

        $this->assertTrue($this->transfer->refresh()->successful);
    }

    public function testFailureStatusUpdateCanBeSentFromOldNode(): void
    {
        $server = $this->transfer->server;
        $oldNode = $this->transfer->oldNode;

        $this->withHeader('Authorization', "Bearer $oldNode->daemon_token_id." . $oldNode->getDecryptedKey())
            ->postJson("/api/remote/servers/{$server->uuid}/transfer/failure")
            ->assertNoContent();

        $this->assertFalse($this->transfer->refresh()->successful);
    }

    public function testFailureStatusUpdateCanBeSentFromNewNode(): void
    {
        $server = $this->transfer->server;
        $newNode = $this->transfer->newNode;

        $this->withHeader('Authorization', "Bearer $newNode->daemon_token_id." . $newNode->getDecryptedKey())
            ->postJson("/api/remote/servers/{$server->uuid}/transfer/failure")
            ->assertNoContent();

        $this->assertFalse($this->transfer->refresh()->successful);
    }

    public function testSuccessStatusUpdateCannotBeSentFromOldNode(): void
    {
        $server = $this->transfer->server;
        $oldNode = $this->transfer->oldNode;

        $this->withHeader('Authorization', "Bearer $oldNode->daemon_token_id." . $oldNode->getDecryptedKey())
            ->postJson("/api/remote/servers/{$server->uuid}/transfer/success")
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'HttpForbiddenException')
            ->assertJsonPath('errors.0.detail', 'Requesting node does not have permission to access this server.');

        $this->assertNull($this->transfer->refresh()->successful);
    }

    public function testSuccessStatusUpdateCannotBeSentFromUnauthorizedNode(): void
    {
        $server = $this->transfer->server;
        $node = Node::factory()->for(Location::factory())->create();

        $this->withHeader('Authorization', "Bearer $node->daemon_token_id." . $node->getDecryptedKey())
            ->postJson("/api/remote/servers/$server->uuid/transfer/success")
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'HttpForbiddenException')
            ->assertJsonPath('errors.0.detail', 'Requesting node does not have permission to access this server.');

        $this->assertNull($this->transfer->refresh()->successful);
    }

    public function testFailureStatusUpdateCannotBeSentFromUnauthorizedNode(): void
    {
        $server = $this->transfer->server;
        $node = Node::factory()->for(Location::factory())->create();

        $this->withHeader('Authorization', "Bearer $node->daemon_token_id." . $node->getDecryptedKey())
            ->postJson("/api/remote/servers/$server->uuid/transfer/failure")->assertForbidden()
            ->assertJsonPath('errors.0.code', 'HttpForbiddenException')
            ->assertJsonPath('errors.0.detail', 'Requesting node does not have permission to access this server.');

        $this->assertNull($this->transfer->refresh()->successful);
    }
}
