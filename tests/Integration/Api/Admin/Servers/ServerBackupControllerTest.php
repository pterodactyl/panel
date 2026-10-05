<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Servers\ServerBackupControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Models\Backup;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root-administrator. */
dataset('backupEndpointsDataProvider', function () {
    return [['getJson', 'api.admin.servers.backups'], ['postJson', 'api.admin.servers.backups.toggle']];
});
test('list backups', function () {
    $server = $this->createServerModel();
    $backups = Backup::factory()->times(2)->create(['server_id' => $server->id]);
    $response = $this->getJson(route('api.admin.servers.backups', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['uuid', 'name', 'is_successful', 'is_locked', 'bytes', 'created_at', 'completed_at']]]]);
    $response->assertJsonFragment(['uuid' => $backups[0]->uuid]);
    $response->assertJsonFragment(['uuid' => $backups[1]->uuid]);
});
test('list backups is scoped to server', function () {
    $server = $this->createServerModel();
    Backup::factory()->create(['server_id' => $server->id]);
    $other = $this->createServerModel();
    Backup::factory()->create(['server_id' => $other->id]);
    $response = $this->getJson(route('api.admin.servers.backups', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
});
test('toggle backup lock', function () {
    $server = $this->createServerModel();
    $backup = Backup::factory()->create(['server_id' => $server->id, 'is_locked' => false, 'ignored_files' => []]);
    $response = $this->postJson(route('api.admin.servers.backups.toggle', ['server' => $server->id, 'backup' => $backup->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('attributes.is_locked', true);
    $this->assertDatabaseHas('backups', ['id' => $backup->id, 'is_locked' => true]);
    $this->postJson(route('api.admin.servers.backups.toggle', ['server' => $server->id, 'backup' => $backup->id]));
    $this->assertDatabaseHas('backups', ['id' => $backup->id, 'is_locked' => false]);
});
test('toggle backup not on server returns not found', function () {
    $server = $this->createServerModel();
    $other = $this->createServerModel();
    $backup = Backup::factory()->create(['server_id' => $other->id]);
    $response = $this->postJson(route('api.admin.servers.backups.toggle', ['server' => $server->id, 'backup' => $backup->id]));
    $this->assertNotFoundJson($response);
});
test('non admin forbidden', function (string $method, string $routeName) {
    $this->actingAsNonAdmin();
    $server = $this->createServerModel();
    $backup = Backup::factory()->create(['server_id' => $server->id]);
    $response = $this->{$method}(route($routeName, ['server' => $server->id, 'backup' => $backup->id]));
    $this->assertAccessDeniedJson($response);
})->with('backupEndpointsDataProvider');
