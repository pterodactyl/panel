<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Extensions\ExtensionFormFieldEndpointsTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFormFieldRegistry;
use Pterodactyl\Services\Extensions\ExtensionRegistration;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonConfiguration;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;
use Pterodactyl\Tests\Support\Fakes\FakeDynamicDatabaseConnection;
use RuntimeException;

use function pterodactylTestCase;

uses(AdminApiIntegrationTestCase::class);

beforeEach(function (): void {
    $this->extensionsDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-form-endpoints-'.uniqid();
    File::ensureDirectoryExists($this->extensionsDirectory.DIRECTORY_SEPARATOR.'fields');
    File::put($this->extensionsDirectory.DIRECTORY_SEPARATOR.'fields'.DIRECTORY_SEPARATOR.'extension.json', json_encode(['id' => 'fields', 'name' => 'fields', 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
    config(['extensions.enabled' => true, 'extensions.directory' => $this->extensionsDirectory]);
    Extension::query()->create(['identifier' => 'fields', 'version' => '1.0.0', 'enabled' => true]);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    Bus::fake();
    config()->set('database.connections.dynamic', config('database.connections.mysql'));
    $this->app->instance(DynamicDatabaseConnection::class, new FakeDynamicDatabaseConnection);
});

afterEach(function (): void {
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    File::deleteDirectory($this->extensionsDirectory);
});

dataset('formEndpoints', [
    'create user' => ['admin.user', 'users.store'],
    'update user' => ['admin.user', 'users.update'],
    'create node' => ['admin.node', 'nodes.store'],
    'update node' => ['admin.node', 'nodes.update'],
    'create server' => ['admin.server', 'servers.store'],
    'update server details' => ['admin.server', 'servers.details'],
    'create egg' => ['admin.egg', 'eggs.store'],
    'update egg' => ['admin.egg', 'eggs.update'],
    'create location' => ['admin.location', 'locations.store'],
    'update location' => ['admin.location', 'locations.update'],
    'create mount' => ['admin.mount', 'mounts.store'],
    'update mount' => ['admin.mount', 'mounts.update'],
    'create database host' => ['admin.databaseHost', 'database-hosts.store'],
    'update database host' => ['admin.databaseHost', 'database-hosts.update'],
]);

test('extension fields are saved with the core change on every resource form', function (string $form, string $endpoint): void {
    $saved = [];
    $this->app->make(ExtensionFormFieldRegistry::class)->register('fields', $form, ['note' => ['required', 'string']], fn (): array => [], function (Model $subject, array $values) use (&$saved): void {
        $saved[] = [$subject::class, $subject->getKey(), $subject->exists, $values];
    }, $this->app->make(ExtensionRegistration::class));
    $case = formCase($endpoint);

    $response = $this->json($case['method'], $case['url'], [...$case['payload'], 'extensions' => ['fields' => ['note' => 'hello', 'extra' => 'dropped']]]);

    $response->assertSuccessful();

    $subject = $case['find']();
    expect($subject)->not->toBeNull();
    expect($saved)->toBe([[$subject::class, $subject->getKey(), true, ['note' => 'hello']]]);
    expect($response->json('attributes.id'))->toBe($subject->getKey());
})->with('formEndpoints');

test('extension rules reject the request before the core change on every resource form', function (string $form, string $endpoint): void {
    $saved = [];
    $this->app->make(ExtensionFormFieldRegistry::class)->register('fields', $form, ['note' => ['required', 'string']], fn (): array => [], function () use (&$saved): void {
        $saved[] = true;
    }, $this->app->make(ExtensionRegistration::class));
    $case = formCase($endpoint);

    $response = $this->json($case['method'], $case['url'], [...$case['payload'], 'extensions' => ['fields' => ['note' => '']]]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'extensions.fields.note');

    expect($case['find']())->toBeNull();
    expect($saved)->toBe([]);
})->with('formEndpoints');

test('a failing extension save rolls back the core change', function (string $form, string $endpoint): void {
    $this->app->make(ExtensionFormFieldRegistry::class)->register('fields', $form, ['note' => ['required', 'string']], fn (): array => [], function (): void {
        throw new RuntimeException('extension store unavailable');
    }, $this->app->make(ExtensionRegistration::class));
    $case = formCase($endpoint);

    $response = $this->json($case['method'], $case['url'], [...$case['payload'], 'extensions' => ['fields' => ['note' => 'hello']]]);

    $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

    expect($case['find']())->toBeNull();
})->with([
    'create user' => ['admin.user', 'users.store'],
    'update user' => ['admin.user', 'users.update'],
    'create node' => ['admin.node', 'nodes.store'],
    'update node' => ['admin.node', 'nodes.update'],
    'update server details' => ['admin.server', 'servers.details'],
    'create egg' => ['admin.egg', 'eggs.store'],
    'update egg' => ['admin.egg', 'eggs.update'],
    'create location' => ['admin.location', 'locations.store'],
    'update location' => ['admin.location', 'locations.update'],
    'create mount' => ['admin.mount', 'mounts.store'],
    'update mount' => ['admin.mount', 'mounts.update'],
    'create database host' => ['admin.databaseHost', 'database-hosts.store'],
    'update database host' => ['admin.databaseHost', 'database-hosts.update'],
]);

test('a failing extension save after provisioning keeps the created server', function (): void {
    $this->app->make(ExtensionFormFieldRegistry::class)->register('fields', 'admin.server', ['note' => ['required', 'string']], fn (): array => [], function (): void {
        throw new RuntimeException('extension store unavailable');
    }, $this->app->make(ExtensionRegistration::class));
    $case = formCase('servers.store');

    $response = $this->json($case['method'], $case['url'], [...$case['payload'], 'extensions' => ['fields' => ['note' => 'hello']]]);

    $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

    expect($case['find']())->not->toBeNull();
    $case['daemon']->assertCreated();
});

test('a node update that Wings cannot receive still saves the node and its extension fields', function (): void {
    $saved = [];
    $this->app->make(ExtensionFormFieldRegistry::class)->register('fields', 'admin.node', ['note' => ['required', 'string']], fn (): array => [], function (Model $subject, array $values) use (&$saved): void {
        $saved[] = $values;
    }, $this->app->make(ExtensionRegistration::class));
    Http::fake(['*' => Http::response('', Response::HTTP_BAD_GATEWAY)]);
    $node = Node::factory()->for(Location::factory())->create();

    $response = $this->putJson(route('api.admin.nodes.update', ['node' => $node->id]), ['name' => 'ExtNode', 'description' => 'Extension node.', 'location_id' => $node->location_id, 'public' => true, 'fqdn' => 'localhost', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes', 'extensions' => ['fields' => ['note' => 'hello']]]);

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'ConfigurationNotPersistedException');

    expect($node->refresh()->name)->toBe('ExtNode');
    expect($saved)->toBe([['note' => 'hello']]);
});

test('requests without extension values leave extension saves alone on every resource form', function (string $form, string $endpoint): void {
    $saved = [];
    $this->app->make(ExtensionFormFieldRegistry::class)->register('fields', $form, ['note' => ['required', 'string']], fn (): array => [], function () use (&$saved): void {
        $saved[] = true;
    }, $this->app->make(ExtensionRegistration::class));
    $case = formCase($endpoint);

    $this->json($case['method'], $case['url'], $case['payload'])->assertSuccessful();

    expect($case['find']())->not->toBeNull();
    expect($saved)->toBe([]);
})->with('formEndpoints');

/**
 * @return array{method: string, url: string, payload: array<string, mixed>, find: callable(): ?Model, daemon: ?FakeDaemonServer}
 */
function formCase(string $endpoint): array
{
    return (function () use ($endpoint): array {
        $daemon = null;
        $route = fn (string $name, array $parameters = []): string => route('api.admin.'.$name, $parameters);
        $userPayload = ['username' => 'ext-user', 'email' => 'ext-user@example.test', 'name_first' => 'Ext', 'name_last' => 'User'];
        $nodePayload = fn (Location $location): array => ['name' => 'ExtNode', 'description' => 'Extension node.', 'location_id' => $location->id, 'public' => true, 'fqdn' => 'localhost', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes'];
        $eggPayload = ['name' => 'Ext Egg', 'description' => 'Extension egg.', 'docker_images' => 'Java 17|ghcr.io/pterodactyl/yolks:java_17', 'startup' => 'java -jar server.jar', 'config_stop' => 'stop', 'config_startup' => '{"done": ["Done"]}', 'config_logs' => '{}', 'config_files' => '{}'];
        $mountPayload = ['name' => 'Ext Mount', 'description' => 'Extension mount.', 'source' => '/mnt/ext-source', 'target' => '/mnt/ext-target', 'read_only' => false, 'user_mountable' => false];
        $hostPayload = ['name' => 'Ext Host', 'host' => '127.0.0.1', 'port' => 3306, 'username' => 'extuser', 'password' => 'extpassword'];

        $case = match ($endpoint) {
            'users.store' => ['POST', $route('users.store'), $userPayload, fn (): ?Model => User::query()->where('username', 'ext-user')->first()],
            'users.update' => (function () use ($route, $userPayload): array {
                $user = User::factory()->create();

                return ['PUT', $route('users.update', ['user' => $user->id]), $userPayload, fn (): ?Model => User::query()->whereKey($user->id)->where('username', 'ext-user')->first()];
            })(),
            'nodes.store' => ['POST', $route('nodes.store'), $nodePayload(Location::factory()->create()), fn (): ?Model => Node::query()->where('name', 'ExtNode')->first()],
            'nodes.update' => (function () use ($route, $nodePayload): array {
                new FakeDaemonConfiguration;
                $node = Node::factory()->for(Location::factory())->create();

                return ['PUT', $route('nodes.update', ['node' => $node->id]), $nodePayload($node->location), fn (): ?Model => Node::query()->whereKey($node->id)->where('name', 'ExtNode')->first()];
            })(),
            'servers.store' => (function () use ($route, &$daemon): array {
                $node = Node::factory()->for(Location::factory())->create();
                $allocation = Allocation::factory()->create(['node_id' => $node->id]);
                $egg = Egg::query()->where('author', 'support@pterodactyl.io')->where('name', 'Bungeecord')->firstOrFail();
                $daemon = new FakeDaemonServer;

                return ['POST', $route('servers.store'), ['name' => 'ExtServer', 'description' => 'Extension server.', 'owner_id' => User::factory()->create()->id, 'egg_id' => $egg->id, 'docker_image' => 'java:8', 'startup' => 'java -jar server.jar', 'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar'], 'memory' => 512, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 0, 'threads' => null, 'database_limit' => 0, 'allocation_limit' => 0, 'backup_limit' => 0, 'primary_allocation_id' => $allocation->id], fn (): ?Model => Server::query()->where('name', 'ExtServer')->first()];
            })(),
            'servers.details' => (function () use ($route): array {
                $server = $this->createServerModel();

                return ['PUT', $route('servers.details', ['server' => $server->id]), ['name' => 'ExtServer', 'owner_id' => $server->owner_id, 'external_id' => null, 'description' => 'Extension server.'], fn (): ?Model => Server::query()->whereKey($server->id)->where('name', 'ExtServer')->first()];
            })(),
            'eggs.store' => ['POST', $route('eggs.store'), $eggPayload, fn (): ?Model => Egg::query()->where('name', 'Ext Egg')->first()],
            'eggs.update' => (function () use ($route, $eggPayload): array {
                $egg = Egg::factory()->create();

                return ['PUT', $route('eggs.update', ['egg' => $egg->id]), $eggPayload, fn (): ?Model => Egg::query()->whereKey($egg->id)->where('name', 'Ext Egg')->first()];
            })(),
            'locations.store' => ['POST', $route('locations.store'), ['short' => 'ext', 'long' => 'Extension location'], fn (): ?Model => Location::query()->where('short', 'ext')->first()],
            'locations.update' => (function () use ($route): array {
                $location = Location::factory()->create();

                return ['PUT', $route('locations.update', ['location' => $location->id]), ['short' => 'ext', 'long' => 'Extension location'], fn (): ?Model => Location::query()->whereKey($location->id)->where('short', 'ext')->first()];
            })(),
            'mounts.store' => ['POST', $route('mounts.store'), $mountPayload, fn (): ?Model => Mount::query()->where('name', 'Ext Mount')->first()],
            'mounts.update' => (function () use ($route, $mountPayload): array {
                $mount = Mount::factory()->create();

                return ['PUT', $route('mounts.update', ['mount' => $mount->id]), $mountPayload, fn (): ?Model => Mount::query()->whereKey($mount->id)->where('name', 'Ext Mount')->first()];
            })(),
            'database-hosts.store' => ['POST', $route('database-hosts.store'), $hostPayload, fn (): ?Model => DatabaseHost::query()->where('name', 'Ext Host')->first()],
            'database-hosts.update' => (function () use ($route, $hostPayload): array {
                $host = DatabaseHost::factory()->create();

                return ['PUT', $route('database-hosts.update', ['databaseHost' => $host->id]), $hostPayload, fn (): ?Model => DatabaseHost::query()->whereKey($host->id)->where('name', 'Ext Host')->first()];
            })(),
        };

        return ['method' => $case[0], 'url' => $case[1], 'payload' => $case[2], 'find' => $case[3], 'daemon' => $daemon];
    })->call(pterodactylTestCase());
}
