<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Servers\ServerManagementTest;

use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonRevocation;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;
use Pterodactyl\Tests\Support\Fakes\FakeDatabaseHostGateway;

uses(ApplicationApiIntegrationTestCase::class);
test('server can be fetched by external id', function (): void {
    $server = $this->createServerModel(['external_id' => 'ext-abc-123']);
    $this->getJson('/api/application/servers/external/ext-abc-123')->assertOk()->assertJsonPath('object', 'server')->assertJsonPath('attributes.uuid', $server->uuid);
    $this->getJson('/api/application/servers/external/does-not-exist')->assertNotFound();
});
test('server can be created', function (): void {
    mockDaemon();
    // Reuse the fixture server's node/egg, then create a fresh unassigned
    // allocation on that node for the new server to claim.
    $fixture = $this->createServerModel();
    $allocation = Allocation::factory()->create(['node_id' => $fixture->node_id]);
    $owner = User::factory()->create();
    $environment = $fixture->egg->variables->mapWithKeys(fn ($variable): array => [$variable->env_variable => $variable->default_value])->toArray();
    $response = $this->postJson('/api/application/servers', ['name' => 'Created Via API', 'user' => $owner->id, 'egg' => $fixture->egg_id, 'docker_image' => 'ghcr.io/pterodactyl/yolks:java_17', 'startup' => 'java -jar server.jar', 'environment' => $environment, 'skip_scripts' => true, 'limits' => ['memory' => 512, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 100], 'feature_limits' => ['databases' => 0, 'allocations' => 1, 'backups' => 0], 'allocation' => ['default' => $allocation->id]]);
    $response->assertStatus(Response::HTTP_CREATED)->assertJsonPath('object', 'server')->assertJsonPath('attributes.name', 'Created Via API')->assertJsonPath('attributes.user', $owner->id);
    $this->assertDatabaseHas('servers', ['name' => 'Created Via API', 'owner_id' => $owner->id]);
    expect($allocation->refresh()->server_id)->toBe($response->json('attributes.id'));
});
test('server details can be updated', function (): void {
    $revocation = new FakeDaemonRevocation;
    $server = $this->createServerModel();
    $previousOwner = $server->user;
    $owner = User::factory()->create();
    $this->patchJson("/api/application/servers/{$server->id}/details", ['name' => 'Renamed Via API', 'user' => $owner->id, 'external_id' => 'ext-renamed', 'description' => 'Updated description.'])->assertOk()->assertJsonPath('attributes.name', 'Renamed Via API')->assertJsonPath('attributes.user', $owner->id)->assertJsonPath('attributes.external_id', 'ext-renamed');
    $server->refresh();
    expect($server->name)->toBe('Renamed Via API');
    expect($server->owner_id)->toBe($owner->id);
    $revocation->assertDeauthorized($previousOwner->uuid, [$server->uuid]);
});
test('server build can be updated', function (): void {
    mockDaemon();
    $server = $this->createServerModel();
    $this->patchJson("/api/application/servers/{$server->id}/build", ['allocation' => $server->allocation_id, 'limits' => ['memory' => 1024, 'swap' => 0, 'io' => 500, 'cpu' => 200, 'disk' => 5120], 'feature_limits' => ['databases' => 5, 'backups' => 2, 'allocations' => 1]])->assertOk()->assertJsonPath('attributes.limits.memory', 1024)->assertJsonPath('attributes.limits.cpu', 200)->assertJsonPath('attributes.feature_limits.databases', 5);
    $server->refresh();
    expect($server->memory)->toBe(1024);
    expect($server->database_limit)->toBe(5);
});
test('server startup can be updated', function (): void {
    mockDaemon();
    $server = $this->createServerModel();
    $environment = $server->egg->variables->mapWithKeys(fn ($variable): array => [$variable->env_variable => $variable->default_value])->toArray();
    $this->patchJson("/api/application/servers/{$server->id}/startup", ['startup' => 'java -jar updated.jar', 'environment' => $environment, 'egg' => $server->egg_id, 'image' => 'ghcr.io/pterodactyl/yolks:java_17', 'skip_scripts' => false])->assertOk()->assertJsonPath('attributes.container.startup_command', 'java -jar updated.jar')->assertJsonPath('attributes.container.image', 'ghcr.io/pterodactyl/yolks:java_17');
    expect($server->refresh()->startup)->toBe('java -jar updated.jar');
});
test('server can be suspended and unsuspended', function (): void {
    mockDaemon();
    $server = $this->createServerModel();
    $this->postJson("/api/application/servers/{$server->id}/suspend")->assertStatus(Response::HTTP_NO_CONTENT);
    expect($server->refresh()->isSuspended())->toBeTrue();
    $this->postJson("/api/application/servers/{$server->id}/unsuspend")->assertStatus(Response::HTTP_NO_CONTENT);
    expect($server->refresh()->isSuspended())->toBeFalse();
});
test('server can be reinstalled', function (): void {
    mockDaemon();
    $server = $this->createServerModel();
    $this->postJson("/api/application/servers/{$server->id}/reinstall")->assertStatus(Response::HTTP_NO_CONTENT);
    expect($server->refresh()->status)->toBe(Server::STATUS_INSTALLING);
});
test('a server that skips its install script cannot be reinstalled', function (): void {
    $fake = new FakeDaemonServer;
    $server = $this->createServerModel(['skip_scripts' => true]);

    $this->getJson("/api/application/servers/{$server->id}")->assertOk()->assertJsonPath('attributes.container.skip_scripts', true);
    $this->postJson("/api/application/servers/{$server->id}/reinstall")
        ->assertStatus(Response::HTTP_BAD_REQUEST)
        ->assertJsonPath('errors.0.detail', trans('admin/server.exceptions.skipping_install_script'));

    expect($server->refresh()->status)->toBeNull()
        ->and($fake->callsFor('reinstall'))->toBeEmpty();
});
test('a server that skips its install script can be reinstalled from an unfinished install', function (string $status): void {
    $fake = new FakeDaemonServer;
    $server = $this->createServerModel(['skip_scripts' => true, 'status' => $status]);

    $this->postJson("/api/application/servers/{$server->id}/reinstall")->assertStatus(Response::HTTP_NO_CONTENT);

    expect($server->refresh()->status)->toBe(Server::STATUS_INSTALLING);
    $fake->assertReinstalled();
})->with([Server::STATUS_INSTALLING, Server::STATUS_INSTALL_FAILED, Server::STATUS_REINSTALL_FAILED]);
test('server can be deleted', function (): void {
    mockDaemon();
    $server = $this->createServerModel();
    $this->deleteJson("/api/application/servers/{$server->id}")->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
});
test('server can be force deleted', function (): void {
    mockDaemon();
    $server = $this->createServerModel();
    $this->deleteJson("/api/application/servers/{$server->id}/force")->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
});
test('server databases can be listed and viewed', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $this->getJson("/api/application/servers/{$server->id}/databases")->assertOk()->assertJsonPath('object', 'list')->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.id', $database->id);
    $this->getJson("/api/application/servers/{$server->id}/databases/{$database->id}")->assertOk()->assertJsonPath('object', 'server_database')->assertJsonPath('attributes.id', $database->id);
});
test('server database can be created', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $this->app->instance(DatabaseHostGateway::class, $gateway = new FakeDatabaseHostGateway());
    $this->postJson("/api/application/servers/{$server->id}/databases", ['database' => 'apidb', 'remote' => '%', 'host' => $host->id])->assertStatus(Response::HTTP_CREATED)->assertJsonPath('object', 'server_database')->assertJsonPath('attributes.database', "s{$server->id}_apidb");
    $database = Database::query()->where('server_id', $server->id)->firstOrFail();
    expect($database->database_host_id)->toBe($host->id);
    $gateway->assertCreated("s{$server->id}_apidb");
    $gateway->assertFlushed(1);
});
test('server database password can be reset', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $this->app->instance(DatabaseHostGateway::class, $gateway = new FakeDatabaseHostGateway());
    $this->postJson("/api/application/servers/{$server->id}/databases/{$database->id}/reset-password")->assertStatus(Response::HTTP_NO_CONTENT);
    $gateway->assertFlushed(1);
    expect($gateway->count('createUser'))->toBe(1);
});
test('server database can be deleted', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $this->app->instance(DatabaseHostGateway::class, $gateway = new FakeDatabaseHostGateway());
    $this->deleteJson("/api/application/servers/{$server->id}/databases/{$database->id}")->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('databases', ['id' => $database->id]);
    $gateway->assertDroppedDatabase($database->database);
    $gateway->assertDroppedUser($database->username, $database->remote);
    $gateway->assertFlushed(1);
});
/** Fake the Wings server endpoints exercised by state-changing requests. */
function mockDaemon(): void
{
    new FakeDaemonServer;
}
