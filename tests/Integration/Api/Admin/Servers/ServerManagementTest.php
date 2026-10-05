<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Servers\ServerManagementTest;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;

uses(AdminApiIntegrationTestCase::class);
test('suspend server', function (): void {
    $server = $this->createServerModel();
    $fake = new FakeDaemonServer;
    $response = $this->postJson(route('api.admin.servers.suspension', ['server' => $server->id]), ['suspended' => true]);
    $response->assertStatus(Response::HTTP_NO_CONTENT);

    expect($server->refresh()->isSuspended())->toBeTrue();
    $fake->assertSynced();
});
test('unsuspend server', function (): void {
    $server = $this->createServerModel();
    $server->update(['status' => Server::STATUS_SUSPENDED]);

    $fake = new FakeDaemonServer;
    $response = $this->postJson(route('api.admin.servers.suspension', ['server' => $server->id]), ['suspended' => false]);
    $response->assertStatus(Response::HTTP_NO_CONTENT);

    expect($server->refresh()->isSuspended())->toBeFalse();
    $fake->assertSynced();
});
test('reinstall server', function (): void {
    $server = $this->createServerModel();
    $fake = new FakeDaemonServer;
    $response = $this->postJson(route('api.admin.servers.reinstall', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_ACCEPTED);

    expect($server->refresh()->status)->toBe(Server::STATUS_INSTALLING);
    $fake->assertReinstalled();
});
test('a server that skips its install script cannot be reinstalled', function (): void {
    $server = $this->createServerModel(['skip_scripts' => true]);
    $fake = new FakeDaemonServer;

    $this->postJson(route('api.admin.servers.reinstall', ['server' => $server->id]))
        ->assertStatus(Response::HTTP_BAD_REQUEST)
        ->assertJsonPath('errors.0.detail', trans('admin/server.exceptions.skipping_install_script'));

    expect($server->refresh()->status)->toBeNull()
        ->and($fake->callsFor('reinstall'))->toBeEmpty();
});
test('rebuild server', function (): void {
    $server = $this->createServerModel();
    $fake = new FakeDaemonServer;
    $response = $this->postJson(route('api.admin.servers.rebuild', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $fake->assertSynced();
    Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), "/api/servers/{$server->uuid}/"));
});
test('transfer progress when no transfer', function (): void {
    $server = $this->createServerModel();
    $response = $this->getJson(route('api.admin.servers.transfer-progress', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
});
test('transfer progress in progress', function (): void {
    $server = $this->createServerModel();
    $target = Node::factory()->create(['location_id' => $server->node->location_id]);
    $transfer = new ServerTransfer();
    $transfer->server_id = $server->id;
    $transfer->old_node = $server->node_id;
    $transfer->new_node = $target->id;
    $transfer->old_allocation = $server->allocation_id;
    $transfer->new_allocation = $server->allocation_id;
    $transfer->successful = null;
    $transfer->save();

    $response = $this->getJson(route('api.admin.servers.transfer-progress', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('object', 'server_transfer');
    $response->assertJsonPath('attributes.id', $transfer->id);
    $response->assertJsonPath('attributes.new_node', $target->id);
});
test('update server details', function (): void {
    Bus::fake();
    $server = $this->createServerModel();
    /** @var User $owner */
    $owner = User::factory()->create();
    $response = $this->putJson(route('api.admin.servers.details', ['server' => $server->id]), ['name' => 'UpdatedServer', 'owner_id' => $owner->id, 'external_id' => 'external-123', 'description' => 'An updated description.']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('object', 'server');
    $response->assertJsonPath('attributes.name', 'UpdatedServer');
    $response->assertJsonPath('attributes.user', $owner->id);
    $response->assertJsonPath('attributes.external_id', 'external-123');
    $this->assertDatabaseHas('servers', ['id' => $server->id, 'name' => 'UpdatedServer', 'owner_id' => $owner->id]);
    Bus::assertDispatched(RevokeSftpAccessJob::class);
});
test('update server build', function (): void {
    $server = $this->createServerModel();
    new FakeDaemonServer;
    $response = $this->putJson(route('api.admin.servers.build', ['server' => $server->id]), ['allocation_id' => $server->allocation_id, 'oom_disabled' => true, 'memory' => 1024, 'swap' => 0, 'io' => 500, 'cpu' => 100, 'threads' => null, 'disk' => 2048, 'database_limit' => 2, 'allocation_limit' => 1, 'backup_limit' => 3]);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('object', 'server');
    $response->assertJsonPath('attributes.limits.memory', 1024);
    $response->assertJsonPath('attributes.limits.disk', 2048);
    $response->assertJsonPath('attributes.feature_limits.databases', 2);
    $response->assertJsonPath('attributes.feature_limits.backups', 3);
    $this->assertDatabaseHas('servers', ['id' => $server->id, 'memory' => 1024, 'disk' => 2048]);
});
test('update server startup', function (): void {
    $server = $this->createServerModel(['skip_scripts' => true]);
    $response = $this->putJson(route('api.admin.servers.startup', ['server' => $server->id]), ['startup' => 'java -jar updated.jar', 'egg_id' => $server->egg_id, 'docker_image' => 'java:11', 'skip_scripts' => true, 'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'updated.jar']]);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('object', 'server');
    $response->assertJsonPath('attributes.container.startup_command', 'java -jar updated.jar');
    $response->assertJsonPath('attributes.container.image', 'java:11');
    $response->assertJsonPath('attributes.container.skip_scripts', true);
    $this->assertDatabaseHas('servers', ['id' => $server->id, 'startup' => 'java -jar updated.jar', 'image' => 'java:11', 'skip_scripts' => true]);
});
test('build rejects unassigned default allocation', function (): void {
    $server = $this->createServerModel();
    /** @var Allocation $allocation */
    $allocation = Allocation::factory()->create(['node_id' => $server->node_id]);
    $response = $this->putJson(route('api.admin.servers.build', ['server' => $server->id]), ['allocation_id' => $allocation->id, 'oom_disabled' => true, 'memory' => 256, 'swap' => 0, 'io' => 500, 'cpu' => 0, 'threads' => null, 'disk' => 512, 'database_limit' => 0, 'allocation_limit' => 0, 'backup_limit' => 0]);
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'DisplayException');
});
