<?php

namespace Integration\Api\Remote;

use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

class ServerTransferControllerTest extends IntegrationTestCase
{
    protected ServerTransfer $transfer;

    public function setup(): void
    {
        parent::setUp();

        $transfer = $this->createServerTransferModel();

        $this->transfer = $transfer;
    }

    public function testSuccessStatusUpdateCanBeSentFromNewNode()
    {
        $server = $this->transfer->server;
        $newNode = $this->transfer->newNode;

        $this->withHeader(
            'Authorization',
            "Bearer $newNode->daemon_token_id." . $newNode->getDecryptedKey()
        )->postJson("/api/remote/servers/$server->uuid/transfer/success")->assertNoContent();

        $this->assertDatabaseHas('server_transfers', ['id' => $this->transfer->id, 'successful' => true]);
    }

    public function testFailureStatusUpdateCanBeSentFromOldNode()
    {
        $server = $this->transfer->server;
        $oldNode = $this->transfer->oldNode;

        $this->withHeader(
            'Authorization',
            "Bearer $oldNode->daemon_token_id." . $oldNode->getDecryptedKey()
        )->postJson("/api/remote/servers/$server->uuid/transfer/failure")->assertNoContent();

        $this->assertDatabaseHas('server_transfers', ['id' => $this->transfer->id, 'successful' => false]);
    }

    public function testFailureStatusUpdateCanBeSentFromNewNode()
    {
        $server = $this->transfer->server;
        $newNode = $this->transfer->newNode;

        $this->withHeader(
            'Authorization',
            "Bearer $newNode->daemon_token_id." . $newNode->getDecryptedKey()
        )->postJson("/api/remote/servers/$server->uuid/transfer/failure")->assertNoContent();

        $this->assertDatabaseHas('server_transfers', ['id' => $this->transfer->id, 'successful' => false]);
    }

    public function testSuccessStatusUpdateCannotBeSentFromOldNode()
    {
        $server = $this->transfer->server;
        $oldNode = $this->transfer->oldNode;

        $response = $this->withHeader(
            'Authorization',
            "Bearer $oldNode->daemon_token_id." . $oldNode->getDecryptedKey()
        )->postJson("/api/remote/servers/$server->uuid/transfer/success")->assertForbidden();

        $response->assertJsonPath('errors.0.code', 'HttpForbiddenException');
        $response->assertJsonPath('errors.0.detail', 'Requesting node does not have permission to access this server.');

        $this->assertDatabaseHas('server_transfers', ['id' => $this->transfer->id, 'successful' => null]);
    }

    public function testSuccessStatusUpdateCannotBeSentFromUnauthorizedNode()
    {
        $server = $this->transfer->server;
        $susNode = $this->createNodeModel();

        $response = $this->withHeader(
            'Authorization',
            "Bearer $susNode->daemon_token_id." . $susNode->getDecryptedKey()
        )->postJson("/api/remote/servers/$server->uuid/transfer/success")->assertForbidden();

        $response->assertJsonPath('errors.0.code', 'HttpForbiddenException');
        $response->assertJsonPath('errors.0.detail', 'Requesting node does not have permission to access this server.');

        $this->assertDatabaseHas('server_transfers', ['id' => $this->transfer->id, 'successful' => null]);
    }

    public function testFailureStatusUpdateCannotBeSentFromUnauthorizedNode()
    {
        $server = $this->transfer->server;
        $susNode = $this->createNodeModel();

        $response = $this->withHeader(
            'Authorization',
            "Bearer $susNode->daemon_token_id." . $susNode->getDecryptedKey()
        )->postJson("/api/remote/servers/$server->uuid/transfer/failure")->assertForbidden();

        $response->assertJsonPath('errors.0.code', 'HttpForbiddenException');
        $response->assertJsonPath('errors.0.detail', 'Requesting node does not have permission to access this server.');

        $this->assertDatabaseHas('server_transfers', ['id' => $this->transfer->id, 'successful' => null]);
    }
}
