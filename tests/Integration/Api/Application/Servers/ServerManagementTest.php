<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Servers\ServerManagementTest;

use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
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
test('a server created through the application api takes its nested fields', function (): void {
    $fake = new FakeDaemonServer;
    $node = Node::factory()->for(Location::factory())->create();
    [$default, $additional] = Allocation::factory()->times(2)->create(['node_id' => $node->id]);

    $response = $this->postJson('/api/application/servers', [
        ...applicationServerPayload($default->id),
        'external_id' => 'app-ext-1',
        'description' => 'Provisioned by billing.',
        'oom_disabled' => false,
        'start_on_completion' => '1',
        'limits' => ['memory' => '512', 'swap' => -1, 'disk' => 1024.0, 'io' => 500, 'cpu' => 100, 'threads' => 0],
        'feature_limits' => ['databases' => null, 'backups' => '2'],
        'allocation' => ['default' => (string) $default->id, 'additional' => [$additional->id]],
    ], options: JSON_PRESERVE_ZERO_FRACTION)->assertCreated();

    $server = Server::query()->findOrFail($response->json('attributes.id'));
    expect($server->external_id)->toBe('app-ext-1')
        ->and($server->description)->toBe('Provisioned by billing.')
        ->and($server->image)->toBe('ghcr.io/pterodactyl/yolks:java_17')
        ->and($server->oom_disabled)->toBeFalse()
        ->and($server->memory)->toBe(512)
        ->and($server->swap)->toBe(-1)
        ->and($server->disk)->toBe(1024)
        ->and($server->cpu)->toBe(100)
        ->and($server->threads)->toBe('0')
        ->and($server->database_limit)->toBe(0)
        ->and($server->allocation_limit)->toBe(0)
        ->and($server->backup_limit)->toBe(2)
        ->and($server->node_id)->toBe($node->id)
        ->and($server->allocation_id)->toBe($default->id)
        ->and($additional->refresh()->server_id)->toBe($server->id);
    $fake->assertCreatedWith(true);
});
test('a server created through the application api keeps its own rules', function (array $payload, string $field, string $rule): void {
    $fake = new FakeDaemonServer;
    $allocation = Allocation::factory()->for(Node::factory()->for(Location::factory()))->create();

    $this->postJson('/api/application/servers', array_filter([...applicationServerPayload($allocation->id), ...$payload], fn (mixed $value): bool => $value !== 'missing'))
        ->assertUnprocessable()
        ->assertJsonCount(1, 'errors')
        ->assertJsonPath('errors.0.meta.source_field', $field)
        ->assertJsonPath('errors.0.meta.rule', $rule);

    expect($fake->callsFor('create'))->toBe([]);
})->with([
    'a missing environment' => [['environment' => 'missing'], 'environment', 'present'],
    'a null oom flag' => [['oom_disabled' => null], 'oom_disabled', 'boolean'],
    'an owner that does not exist' => [['user' => 0], 'user', 'exists'],
    'an egg that does not exist' => [['egg' => 'none'], 'egg', 'exists'],
    'a docker image with shell characters' => [['docker_image' => 'java;rm -rf /'], 'docker_image', 'regex'],
    'a missing database limit' => [['feature_limits' => ['allocations' => 0, 'backups' => 0]], 'feature_limits.databases', 'present'],
    'a negative memory limit' => [['limits' => ['memory' => -1, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 100]], 'limits.memory', 'min'],
    'a missing default allocation' => [['allocation' => ['additional' => []]], 'allocation.default', 'required'],
]);
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
test('server build accepts integer strings and whole floats', function (): void {
    mockDaemon();
    $server = $this->createServerModel();
    $allocation = Allocation::factory()->create(['node_id' => $server->node_id]);

    $this->patchJson("/api/application/servers/{$server->id}/build", ['allocation' => $server->allocation_id, 'add_allocations' => [(string) $allocation->id], 'limits' => ['memory' => 1024.0, 'swap' => '0', 'io' => 500, 'cpu' => 200, 'disk' => 5120], 'feature_limits' => ['databases' => 0, 'backups' => 0, 'allocations' => 2]])
        ->assertOk()
        ->assertJsonPath('attributes.limits.memory', 1024);

    expect($allocation->refresh()->server_id)->toBe($server->id);
});
test('server build rejects fractional limits with a validation error', function (): void {
    $server = $this->createServerModel();

    $this->patchJson("/api/application/servers/{$server->id}/build", ['allocation' => $server->allocation_id, 'limits' => ['memory' => 1024.5, 'swap' => 0, 'io' => 500, 'cpu' => 200, 'disk' => 5120], 'feature_limits' => ['databases' => 0, 'backups' => 0, 'allocations' => 1]])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'limits.memory');
});
test('server build takes the legacy flat limits, and limits over them', function (): void {
    $fake = new FakeDaemonServer;
    $server = $this->createServerModel(['oom_disabled' => true, 'threads' => '0-1', 'database_limit' => 4, 'allocation_limit' => 5, 'backup_limit' => 6]);
    [$added, $removed] = Allocation::factory()->times(2)->create(['node_id' => $server->node_id]);
    $removed->update(['server_id' => $server->id]);

    $this->patchJson("/api/application/servers/{$server->id}/build", ['allocation' => (string) $server->allocation_id, 'oom_disabled' => '0', 'memory' => '768', 'swap' => -1, 'io' => 400, 'cpu' => 50, 'threads' => 2, 'disk' => 4096, 'add_allocations' => [$added->id], 'remove_allocations' => [(string) $removed->id], 'feature_limits' => ['databases' => null, 'backups' => '1']])
        ->assertOk()
        ->assertJsonPath('attributes.limits.memory', 768)
        ->assertJsonPath('attributes.limits.threads', '2');

    $server->refresh();
    expect($server->oom_disabled)->toBeFalse()
        ->and($server->memory)->toBe(768)
        ->and($server->swap)->toBe(-1)
        ->and($server->io)->toBe(400)
        ->and($server->cpu)->toBe(50)
        ->and($server->threads)->toBe('2')
        ->and($server->disk)->toBe(4096)
        ->and($server->database_limit)->toBeNull()
        ->and($server->allocation_limit)->toBeNull()
        ->and($server->backup_limit)->toBe(1)
        ->and($added->refresh()->server_id)->toBe($server->id)
        ->and($removed->refresh()->server_id)->toBeNull();
    $fake->assertSynced();

    $this->patchJson("/api/application/servers/{$server->id}/build", ['allocation' => $server->allocation_id, 'memory' => 128, 'threads' => '3', 'limits' => ['memory' => 256, 'swap' => 0, 'io' => 500, 'cpu' => 100, 'disk' => 1024, 'threads' => null], 'feature_limits' => ['databases' => 1, 'backups' => 2, 'allocations' => 3]])
        ->assertOk();

    $server->refresh();
    expect($server->memory)->toBe(256)
        ->and($server->threads)->toBeNull()
        ->and($server->allocation_limit)->toBe(3);
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
test('a blocked reinstall returns an error to clients not requesting json', function (): void {
    $fake = new FakeDaemonServer;
    $server = $this->createServerModel(['skip_scripts' => true]);

    $this->post("/api/application/servers/{$server->id}/reinstall", [], ['Accept' => '*/*'])
        ->assertStatus(Response::HTTP_BAD_REQUEST)
        ->assertJsonPath('errors.0.detail', trans('admin/server.exceptions.skipping_install_script'));

    expect($fake->callsFor('reinstall'))->toBeEmpty();
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
test('a server database created through the application api takes the default connection limit', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $this->app->instance(DatabaseHostGateway::class, new FakeDatabaseHostGateway());
    $this->postJson("/api/application/servers/{$server->id}/databases", ['database' => 'apidb', 'remote' => '%', 'host' => (string) $host->id])->assertStatus(Response::HTTP_CREATED);
    expect(Database::query()->where('server_id', $server->id)->firstOrFail()->max_connections)->toBe(0);
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
/** @return array<string, mixed> */
function applicationServerPayload(int $allocationId): array
{
    return [
        'name' => 'Application Server',
        'user' => User::factory()->create()->id,
        'egg' => Egg::query()->where('author', 'support@pterodactyl.io')->where('name', 'Bungeecord')->firstOrFail()->id,
        'docker_image' => 'ghcr.io/pterodactyl/yolks:java_17',
        'startup' => 'java -jar server.jar',
        'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar'],
        'limits' => ['memory' => 512, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 100],
        'feature_limits' => ['databases' => 0, 'allocations' => 0, 'backups' => 0],
        'allocation' => ['default' => $allocationId],
    ];
}
/** Fake the Wings server endpoints exercised by state-changing requests. */
function mockDaemon(): void
{
    new FakeDaemonServer;
}
