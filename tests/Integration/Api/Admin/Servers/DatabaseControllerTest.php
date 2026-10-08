<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Servers\DatabaseControllerTest;

use Illuminate\Http\Response;
use Illuminate\Testing\Assert;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDatabaseHostGateway;
use RuntimeException;

use function pterodactylTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a user that is not a root administrator. */
dataset('databaseEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.servers.databases'], ['getJson', 'api.admin.servers.databases.view'], ['postJson', 'api.admin.servers.databases.store'], ['postJson', 'api.admin.servers.databases.rotate-password'], ['delete', 'api.admin.servers.databases.delete']]);
test('get databases', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $databases = Database::factory()->times(2)->create(['server_id' => $server->id, 'database_host_id' => $host->id, 'max_connections' => 0]);
    $response = $this->getJson(route('api.admin.servers.databases', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'host' => ['address', 'port'], 'name', 'username', 'connections_from', 'max_connections']], ['object', 'attributes' => ['id', 'host' => ['address', 'port'], 'name', 'username', 'connections_from', 'max_connections']]]]);
    Assert::assertArraySubset(['object' => 'server_database', 'attributes' => ['id' => hashid($databases[0]->id), 'host' => ['address' => $host->host, 'port' => $host->port], 'name' => $databases[0]->database, 'username' => $databases[0]->username, 'connections_from' => $databases[0]->remote, 'max_connections' => 0]], collect($response->json('data'))->firstWhere('attributes.id', hashid($databases[0]->id)), true);
    Assert::assertArraySubset(['object' => 'server_database', 'attributes' => ['id' => hashid($databases[1]->id), 'host' => ['address' => $host->host, 'port' => $host->port], 'name' => $databases[1]->database, 'username' => $databases[1]->username, 'connections_from' => $databases[1]->remote, 'max_connections' => 0]], collect($response->json('data'))->firstWhere('attributes.id', hashid($databases[1]->id)), true);
});
test('get databases by server short uuid', function (): void {
    $server = $this->createServerModel(['uuidShort' => 'feedbabe']);
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $response = $this->getJson(route('api.admin.servers.databases', ['server' => $server->uuidShort]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.attributes.name', $database->database);
});
test('get single database', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id, 'max_connections' => 25]);
    $response = $this->getJson(route('api.admin.servers.databases.view', ['server' => $server->id, 'database' => hashid($database->id)]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJson(['object' => 'server_database', 'attributes' => ['id' => hashid($database->id), 'host' => ['address' => $host->host, 'port' => $host->port], 'name' => $database->database, 'username' => $database->username, 'connections_from' => $database->remote, 'max_connections' => 25]], true);
});
test('get single database with password include', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id, 'password' => encrypt('s3cr3t-password')]);
    $response = $this->getJson(route('api.admin.servers.databases.view', ['server' => $server->id, 'database' => hashid($database->id), 'include' => 'password']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'name', 'relationships' => ['password' => ['object', 'attributes' => ['password']]]]]);
    $response->assertJsonPath('object', 'server_database');
    $response->assertJsonPath('attributes.relationships.password.attributes.password', 's3cr3t-password');
});
test('get database not on server', function (): void {
    $server = $this->createServerModel();
    $other = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $other->id, 'database_host_id' => $host->id]);
    $response = $this->getJson(route('api.admin.servers.databases.view', ['server' => $server->id, 'database' => hashid($database->id)]));
    $this->assertNotFoundJson($response);
});
test('create database', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $this->app->instance(DatabaseHostGateway::class, $gateway = new FakeDatabaseHostGateway());
    $response = $this->postJson(route('api.admin.servers.databases.store', ['server' => $server->id]), ['database' => 'admindb', 'remote' => '%', 'database_host_id' => $host->id, 'max_connections' => 25]);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'host' => ['address', 'port'], 'name', 'username', 'connections_from', 'max_connections', 'relationships' => ['password' => ['attributes' => ['password']]]], 'meta' => ['resource']]);
    $response->assertJsonPath('object', 'server_database');
    $response->assertJsonPath('attributes.name', 's'.$server->id.'_admindb');

    $database = Database::query()->where('server_id', $server->id)->firstOrFail();
    expect($database->remote)->toBe('%');
    expect($database->database_host_id)->toBe($host->id);
    expect($database->max_connections)->toBe(25);

    $response->assertJsonPath('meta.resource', route('api.admin.servers.databases.view', ['server' => $server->id, 'database' => $database->id]));
    $gateway->assertCreated('s'.$server->id.'_admindb');
    $gateway->assertAssigned('s'.$server->id.'_admindb', $database->username, '%');
    $gateway->assertFlushed(1);
});
test('create database validation', function (): void {
    $server = $this->createServerModel();
    $response = $this->postJson(route('api.admin.servers.databases.store', ['server' => $server->id]), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    $error = $errors->firstWhere('meta.source_field', 'database');
    expect($error)->not->toBeNull('Expected a validation error for the [database] field.');
    expect($error['meta']['rule'])->toBe('required');
    expect($error['detail'])->not->toBeEmpty();
});
test('rotate password', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $this->app->instance(DatabaseHostGateway::class, $gateway = new FakeDatabaseHostGateway());
    $response = $this->postJson(route('api.admin.servers.databases.rotate-password', ['server' => $server->id, 'database' => hashid($database->id)]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'name', 'relationships' => ['password' => ['attributes' => ['password']]]]]);
    $response->assertJsonPath('object', 'server_database');

    $gateway->assertFlushed(1);
    expect($gateway->count('createUser'))->toBe(1);
    $this->assertDatabaseHas('activity_logs', ['event' => 'admin:server-database.rotate-password']);
});
test('a failed password rotation keeps the stored password and logs no activity', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $password = $database->password;
    $this->app->instance(DatabaseHostGateway::class, $gateway = new FakeDatabaseHostGateway());
    $gateway->throwOn['createUser'] = new RuntimeException('remote failure');
    $this->withoutExceptionHandling();

    expect(fn () => $this->postJson(route('api.admin.servers.databases.rotate-password', ['server' => $server->id, 'database' => hashid($database->id)])))
        ->toThrow(RuntimeException::class);

    expect($database->refresh()->password)->toBe($password);
    $this->assertDatabaseMissing('activity_logs', ['event' => 'admin:server-database.rotate-password']);
});
test('rotate password database not on server', function (): void {
    $server = $this->createServerModel();
    $other = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $other->id, 'database_host_id' => $host->id]);
    $response = $this->postJson(route('api.admin.servers.databases.rotate-password', ['server' => $server->id, 'database' => hashid($database->id)]));
    $this->assertNotFoundJson($response);
});
test('delete database', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $this->app->instance(DatabaseHostGateway::class, $gateway = new FakeDatabaseHostGateway());
    $response = $this->delete(route('api.admin.servers.databases.delete', ['server' => $server->id, 'database' => hashid($database->id)]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('databases', ['id' => $database->id]);
    $gateway->assertDroppedDatabase($database->database);
    $gateway->assertDroppedUser($database->username, $database->remote);
    $gateway->assertFlushed(1);
});
test('delete database not on server', function (): void {
    $server = $this->createServerModel();
    $other = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $other->id, 'database_host_id' => $host->id]);
    $response = $this->delete(route('api.admin.servers.databases.delete', ['server' => $server->id, 'database' => hashid($database->id)]));
    $this->assertNotFoundJson($response);
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $this->actingAsNonAdmin();
    $response = $this->{$method}(route($routeName, ['server' => $server->id, 'database' => hashid($database->id)]));
    $this->assertAccessDeniedJson($response);
})->with('databaseEndpointsDataProvider');
/** Return the HashID encoded identifier for a database, as the route binding resolves it. */
function hashid(int $id): string
{
    return (fn () => $this->app->make(HashidsInterface::class)->encode($id))->call(pterodactylTestCase());
}
