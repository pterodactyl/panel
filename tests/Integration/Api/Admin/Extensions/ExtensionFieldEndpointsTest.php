<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Extensions\ExtensionFieldEndpointsTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Extensions\Fields;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFieldRegistry;
use Pterodactyl\Services\Extensions\ExtensionRegistration;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonConfiguration;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;
use Pterodactyl\Tests\Support\Fakes\FakeDynamicDatabaseConnection;
use RuntimeException;

use function pterodactylTestCase;

uses(AdminApiIntegrationTestCase::class);

final class NoteFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['note' => ['required', 'string', 'max:32']];
    }
}

final class FailingSaveFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['note' => ['required', 'string']];
    }

    /** @return array<string, null> */
    public function values(Model $model): array
    {
        return ['note' => null];
    }

    /** @param array<string, string> $values */
    public function save(Model $model, array $values): void
    {
        throw new RuntimeException('The billing system is down.');
    }
}

beforeEach(function (): void {
    $this->extensionsDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-field-endpoints-'.uniqid();
    File::ensureDirectoryExists($this->extensionsDirectory.DIRECTORY_SEPARATOR.'fields');
    File::put($this->extensionsDirectory.DIRECTORY_SEPARATOR.'fields'.DIRECTORY_SEPARATOR.'extension.json', json_encode(['id' => 'fields', 'name' => 'Fields', 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
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

const CREATE_ENDPOINTS = [
    'create user' => [User::class, 'users.store'],
    'create node' => [Node::class, 'nodes.store'],
    'create server' => [Server::class, 'servers.store'],
    'create egg' => [Egg::class, 'eggs.store'],
    'create location' => [Location::class, 'locations.store'],
    'create mount' => [Mount::class, 'mounts.store'],
    'create database host' => [DatabaseHost::class, 'database-hosts.store'],
];

const UPDATE_ENDPOINTS = [
    'update user' => [User::class, 'users.update'],
    'update node' => [Node::class, 'nodes.update'],
    'update server details' => [Server::class, 'servers.details'],
    'update egg' => [Egg::class, 'eggs.update'],
    'update location' => [Location::class, 'locations.update'],
    'update mount' => [Mount::class, 'mounts.update'],
    'update database host' => [DatabaseHost::class, 'database-hosts.update'],
];

dataset('createEndpoints', CREATE_ENDPOINTS);
dataset('updateEndpoints', UPDATE_ENDPOINTS);
dataset('formEndpoints', [...CREATE_ENDPOINTS, ...UPDATE_ENDPOINTS]);

test('values are validated, saved with the change and returned on the resource', function (string $model, string $endpoint): void {
    registerFields($model, NoteFields::class);
    $case = formCase($endpoint);

    $response = $this->json($case['method'], $case['url'], [...$case['payload'], 'extensions' => ['fields' => ['note' => 'hello', 'extra' => 'dropped']]]);

    $response->assertSuccessful();
    $response->assertJsonPath('attributes.extensions', ['fields' => ['note' => 'hello']]);

    $subject = $case['find']();
    expect($subject)->not->toBeNull();
    expect($this->app->make(ExtensionRepository::class)->settings('fields')->for($subject)->all())->toBe(['note' => 'hello']);
})->with('formEndpoints');

test('invalid values reject the request before the core change', function (string $model, string $endpoint): void {
    registerFields($model, NoteFields::class);
    $case = formCase($endpoint);

    $response = $this->json($case['method'], $case['url'], [...$case['payload'], 'extensions' => ['fields' => ['note' => str_repeat('a', 40)]]]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'extensions.fields.note');
    $response->assertJsonPath('errors.0.detail', 'The note may not be greater than 32 characters.');
    expect($case['find']())->toBeNull();
})->with('formEndpoints');

test('a save that throws rolls the core change back', function (string $model, string $endpoint): void {
    registerFields($model, FailingSaveFields::class);
    $case = formCase($endpoint);

    $this->json($case['method'], $case['url'], [...$case['payload'], 'extensions' => ['fields' => ['note' => 'hello']]])
        ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

    expect($case['find']())->toBeNull();
})->with('formEndpoints');

test('a request that sends no values leaves the stored ones alone', function (string $model, string $endpoint): void {
    registerFields($model, NoteFields::class);
    $case = formCase($endpoint);
    $settings = $this->app->make(ExtensionRepository::class)->settings('fields');
    $settings->for($case['subject'] ?? throw new RuntimeException('Expected a model to update.'))->set('note', 'kept');

    $this->json($case['method'], $case['url'], $case['payload'])
        ->assertSuccessful()
        ->assertJsonPath('attributes.extensions.fields.note', 'kept');

    expect($settings->for($case['subject'])->all())->toBe(['note' => 'kept']);
})->with('updateEndpoints');

test('a node update that Wings cannot receive keeps the node and its values', function (): void {
    registerFields(Node::class, NoteFields::class);
    Http::fake(['*' => Http::response('', Response::HTTP_BAD_GATEWAY)]);
    $case = formCase('nodes.update');

    $response = $this->json($case['method'], $case['url'], [...$case['payload'], 'extensions' => ['fields' => ['note' => 'hello']]]);

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'ConfigurationNotPersistedException');
    $node = $case['find']();
    expect($node)->not->toBeNull();
    expect($this->app->make(ExtensionRepository::class)->settings('fields')->for($node)->get('note'))->toBe('hello');
});

test('lists leave extension values out', function (): void {
    registerFields(User::class, NoteFields::class);

    $this->getJson(route('api.admin.users'))
        ->assertOk()
        ->assertJsonMissingPath('data.0.attributes.extensions');
});

/**
 * @param  class-string<Model>  $model
 * @param  class-string<Fields>  $fields
 */
function registerFields(string $model, string $fields): void
{
    (function () use ($model, $fields): void {
        $this->app->make(ExtensionFieldRegistry::class)->register('fields', $model, $fields, $this->app->make(ExtensionRegistration::class));
    })->call(pterodactylTestCase());
}

/**
 * @return array{method: string, url: string, payload: array<string, mixed>, find: callable(): ?Model, subject: ?Model}
 */
function formCase(string $endpoint): array
{
    return (function () use ($endpoint): array {
        $route = fn (string $name, array $parameters = []): string => route('api.admin.'.$name, $parameters);
        $userPayload = ['username' => 'ext-user', 'email' => 'ext-user@example.test', 'name_first' => 'Ext', 'name_last' => 'User'];
        $nodePayload = fn (Location $location): array => ['name' => 'ExtNode', 'description' => 'Extension node.', 'location_id' => $location->id, 'public' => true, 'fqdn' => 'localhost', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes'];
        $eggPayload = ['name' => 'Ext Egg', 'description' => 'Extension egg.', 'docker_images' => 'Java 17|ghcr.io/pterodactyl/yolks:java_17', 'startup' => 'java -jar server.jar', 'config_stop' => 'stop', 'config_startup' => '{"done": ["Done"]}', 'config_logs' => '{}', 'config_files' => '{}'];
        $mountPayload = ['name' => 'Ext Mount', 'description' => 'Extension mount.', 'source' => '/mnt/ext-source', 'target' => '/mnt/ext-target', 'read_only' => false, 'user_mountable' => false];
        $hostPayload = ['name' => 'Ext Host', 'host' => '127.0.0.1', 'port' => 3306, 'username' => 'extuser', 'password' => 'extpassword'];

        $case = match ($endpoint) {
            'users.store' => ['POST', $route('users.store'), $userPayload, fn (): ?Model => User::query()->where('username', 'ext-user')->first(), null],
            'users.update' => (function () use ($route, $userPayload): array {
                $user = User::factory()->create();

                return ['PUT', $route('users.update', ['user' => $user->id]), $userPayload, fn (): ?Model => User::query()->whereKey($user->id)->where('username', 'ext-user')->first(), $user];
            })(),
            'nodes.store' => ['POST', $route('nodes.store'), $nodePayload(Location::factory()->create()), fn (): ?Model => Node::query()->where('name', 'ExtNode')->first(), null],
            'nodes.update' => (function () use ($route, $nodePayload): array {
                new FakeDaemonConfiguration;
                $node = Node::factory()->for(Location::factory())->create();

                return ['PUT', $route('nodes.update', ['node' => $node->id]), $nodePayload($node->location), fn (): ?Model => Node::query()->whereKey($node->id)->where('name', 'ExtNode')->first(), $node];
            })(),
            'servers.store' => (function () use ($route): array {
                $node = Node::factory()->for(Location::factory())->create();
                $allocation = Allocation::factory()->create(['node_id' => $node->id]);
                $egg = Egg::query()->where('author', 'support@pterodactyl.io')->where('name', 'Bungeecord')->firstOrFail();
                new FakeDaemonServer;

                return ['POST', $route('servers.store'), ['name' => 'ExtServer', 'description' => 'Extension server.', 'owner_id' => User::factory()->create()->id, 'egg_id' => $egg->id, 'docker_image' => 'java:8', 'startup' => 'java -jar server.jar', 'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar'], 'memory' => 512, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 0, 'threads' => null, 'database_limit' => 0, 'allocation_limit' => 0, 'backup_limit' => 0, 'primary_allocation_id' => $allocation->id], fn (): ?Model => Server::query()->where('name', 'ExtServer')->first(), null];
            })(),
            'servers.details' => (function () use ($route): array {
                $server = $this->createServerModel();

                return ['PUT', $route('servers.details', ['server' => $server->id]), ['name' => 'ExtServer', 'owner_id' => $server->owner_id, 'external_id' => null, 'description' => 'Extension server.'], fn (): ?Model => Server::query()->whereKey($server->id)->where('name', 'ExtServer')->first(), $server];
            })(),
            'eggs.store' => ['POST', $route('eggs.store'), $eggPayload, fn (): ?Model => Egg::query()->where('name', 'Ext Egg')->first(), null],
            'eggs.update' => (function () use ($route, $eggPayload): array {
                $egg = Egg::factory()->create();

                return ['PUT', $route('eggs.update', ['egg' => $egg->id]), $eggPayload, fn (): ?Model => Egg::query()->whereKey($egg->id)->where('name', 'Ext Egg')->first(), $egg];
            })(),
            'locations.store' => ['POST', $route('locations.store'), ['short' => 'ext', 'long' => 'Extension location'], fn (): ?Model => Location::query()->where('short', 'ext')->first(), null],
            'locations.update' => (function () use ($route): array {
                $location = Location::factory()->create();

                return ['PUT', $route('locations.update', ['location' => $location->id]), ['short' => 'ext', 'long' => 'Extension location'], fn (): ?Model => Location::query()->whereKey($location->id)->where('short', 'ext')->first(), $location];
            })(),
            'mounts.store' => ['POST', $route('mounts.store'), $mountPayload, fn (): ?Model => Mount::query()->where('name', 'Ext Mount')->first(), null],
            'mounts.update' => (function () use ($route, $mountPayload): array {
                $mount = Mount::factory()->create();

                return ['PUT', $route('mounts.update', ['mount' => $mount->id]), $mountPayload, fn (): ?Model => Mount::query()->whereKey($mount->id)->where('name', 'Ext Mount')->first(), $mount];
            })(),
            'database-hosts.store' => ['POST', $route('database-hosts.store'), $hostPayload, fn (): ?Model => DatabaseHost::query()->where('name', 'Ext Host')->first(), null],
            'database-hosts.update' => (function () use ($route, $hostPayload): array {
                $host = DatabaseHost::factory()->create();

                return ['PUT', $route('database-hosts.update', ['databaseHost' => $host->id]), $hostPayload, fn (): ?Model => DatabaseHost::query()->whereKey($host->id)->where('name', 'Ext Host')->first(), $host];
            })(),
        };

        return ['method' => $case[0], 'url' => $case[1], 'payload' => $case[2], 'find' => $case[3], 'subject' => $case[4]];
    })->call(pterodactylTestCase());
}
