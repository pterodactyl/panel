<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\DatabaseHosts\DatabaseHostControllerTest;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Testing\Assert;
use PDOException;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDynamicDatabaseConnection;

use function pterodactylTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root-administrator. */
dataset('databaseHostEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.database-hosts'], ['getJson', 'api.admin.database-hosts.view'], ['getJson', 'api.admin.database-hosts.databases'], ['postJson', 'api.admin.database-hosts.store'], ['putJson', 'api.admin.database-hosts.update'], ['delete', 'api.admin.database-hosts.delete']]);
test('get database hosts', function (): void {
    $hosts = DatabaseHost::factory()->times(2)->create();
    $response = $this->getJson(route('api.admin.database-hosts', ['per_page' => 60]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'name', 'host', 'port', 'username', 'max_databases', 'node_id', 'created_at', 'updated_at']], ['object', 'attributes' => ['id', 'name', 'host', 'port', 'username', 'max_databases', 'node_id', 'created_at', 'updated_at']]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJson(['object' => 'list', 'data' => [[], []], 'meta' => ['pagination' => ['total' => 2, 'count' => 2, 'per_page' => 60, 'current_page' => 1, 'total_pages' => 1]]]);
    Assert::assertArraySubset(['object' => 'database_host', 'attributes' => ['id' => $hosts[0]->id, 'name' => $hosts[0]->name, 'host' => $hosts[0]->host, 'port' => $hosts[0]->port, 'username' => $hosts[0]->username, 'max_databases' => $hosts[0]->max_databases, 'node_id' => $hosts[0]->node_id]], collect($response->json('data'))->firstWhere('attributes.id', $hosts[0]->id), true);
    Assert::assertArraySubset(['object' => 'database_host', 'attributes' => ['id' => $hosts[1]->id, 'name' => $hosts[1]->name, 'host' => $hosts[1]->host, 'port' => $hosts[1]->port, 'username' => $hosts[1]->username, 'max_databases' => $hosts[1]->max_databases, 'node_id' => $hosts[1]->node_id]], collect($response->json('data'))->firstWhere('attributes.id', $hosts[1]->id), true);
    $response->assertJsonMissing(['password' => $hosts[0]->password]);
});
test('get database hosts filtered by name', function (): void {
    $host = DatabaseHost::factory()->create(['name' => 'production-cluster']);
    DatabaseHost::factory()->create(['name' => 'staging-cluster']);
    $response = $this->getJson(route('api.admin.database-hosts', ['filter' => ['name' => 'production-cluster']]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    Assert::assertArraySubset(['object' => 'database_host', 'attributes' => ['id' => $host->id, 'name' => $host->name, 'host' => $host->host, 'port' => $host->port, 'username' => $host->username, 'max_databases' => $host->max_databases, 'node_id' => $host->node_id]], collect($response->json('data'))->firstWhere('attributes.id', $host->id), true);
});
test('get database hosts sorted by name and creation date', function (): void {
    $older = DatabaseHost::factory()->create(['name' => 'ZZ Sort Host Zebra', 'created_at' => now()->subMinute()]);
    $newer = DatabaseHost::factory()->create(['name' => 'ZZ Sort Host Alpha', 'created_at' => now()]);
    $byName = $this->getJson(route('api.admin.database-hosts', ['filter' => ['name' => 'ZZ Sort Host'], 'sort' => 'name']));
    $byName->assertOk()->assertJsonPath('data.0.attributes.id', $newer->id);
    $byCreation = $this->getJson(route('api.admin.database-hosts', ['filter' => ['name' => 'ZZ Sort Host'], 'sort' => '-created_at']));
    $byCreation->assertOk()->assertJsonPath('data.0.attributes.id', $newer->id);
    $this->assertNotSame($older->id, $newer->id);
});
test('get single database host', function (): void {
    $host = DatabaseHost::factory()->create();
    $server = $this->createServerModel();
    Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $response = $this->getJson(route('api.admin.database-hosts.view', ['databaseHost' => $host->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'name', 'host', 'port', 'username', 'max_databases', 'node_id', 'created_at', 'updated_at']]);
    $response->assertJson(['object' => 'database_host', 'attributes' => ['id' => $host->id, 'name' => $host->name, 'host' => $host->host, 'port' => $host->port, 'username' => $host->username, 'max_databases' => $host->max_databases, 'node_id' => $host->node_id]]);
    $response->assertJsonMissing(['password' => $host->password]);
    $response->assertJsonMissing(['relationships' => ['databases']]);
});
test('get database host databases', function (): void {
    $host = DatabaseHost::factory()->create();
    $server = $this->createServerModel();
    Database::factory()->times(2)->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $response = $this->getJson(route('api.admin.database-hosts.databases', ['databaseHost' => $host->id, 'per_page' => 1]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['server' => ['id', 'name'], 'name', 'username', 'connections_from', 'max_connections']]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJsonPath('meta.pagination.total', 2);
});
test('get missing database host', function (): void {
    $response = $this->getJson(route('api.admin.database-hosts.view', ['databaseHost' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('create database host', function (): void {
    mockDynamicConnection();
    $response = $this->postJson(route('api.admin.database-hosts.store'), ['name' => 'Test Host', 'host' => '127.0.0.1', 'port' => 3306, 'username' => 'someuser', 'password' => 'somepassword']);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonCount(3);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'name', 'host', 'port', 'username', 'max_databases', 'node_id', 'created_at', 'updated_at'], 'meta' => ['resource']]);
    $this->assertDatabaseHas('database_hosts', ['name' => 'Test Host', 'host' => '127.0.0.1', 'username' => 'someuser']);
    $host = DatabaseHost::query()->where('name', 'Test Host')->first();
    $response->assertJson(['object' => 'database_host', 'attributes' => ['id' => $host->id, 'name' => $host->name, 'host' => $host->host, 'port' => $host->port, 'username' => $host->username, 'max_databases' => $host->max_databases, 'node_id' => $host->node_id], 'meta' => ['resource' => route('api.admin.database-hosts.view', ['databaseHost' => $host->id])]], true);
    // Plaintext password is never returned and is stored encrypted.
    $response->assertJsonMissing(['password' => 'somepassword']);

    expect(Crypt::decrypt($host->password))->toBe('somepassword');
});
test('create database host connection failure', function (): void {
    $fake = new FakeDynamicDatabaseConnection();
    $fake->throwOnSet = new PDOException('connection refused');

    $this->app->instance(DynamicDatabaseConnection::class, $fake);
    $response = $this->postJson(route('api.admin.database-hosts.store'), ['name' => 'Broken Host', 'host' => '127.0.0.1', 'port' => 3306, 'username' => 'someuser', 'password' => 'somepassword']);
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'DisplayException');
    $response->assertJsonFragment(['detail' => 'There was an error while trying to connect to the host or while executing a query: "connection refused"']);
});
test('create database host validation', function (): void {
    $response = $this->postJson(route('api.admin.database-hosts.store'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    foreach (['name', 'host', 'port', 'username'] as $field) {
        $error = $errors->firstWhere('meta.source_field', $field);
        expect($error)->not->toBeNull("Expected a validation error for the [{$field}] field.");
        expect($error['meta']['rule'])->toBe('required');
        expect($error['detail'])->not->toBeEmpty();
    }
});
test('update database host', function (): void {
    mockDynamicConnection();
    $host = DatabaseHost::factory()->create();
    $response = $this->putJson(route('api.admin.database-hosts.update', ['databaseHost' => $host->id]), ['name' => 'Updated Host', 'host' => '127.0.0.1', 'port' => 3306, 'username' => 'updateduser', 'password' => '']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'name', 'host', 'port', 'username', 'max_databases', 'node_id', 'created_at', 'updated_at']]);
    $this->assertDatabaseHas('database_hosts', ['id' => $host->id, 'name' => 'Updated Host', 'username' => 'updateduser']);
    $host = $host->fresh();
    $response->assertJson(['object' => 'database_host', 'attributes' => ['id' => $host->id, 'name' => $host->name, 'host' => $host->host, 'port' => $host->port, 'username' => $host->username, 'max_databases' => $host->max_databases, 'node_id' => $host->node_id]]);
    $response->assertJsonMissing(['password' => $host->password]);
});
test('update database host validation', function (): void {
    $host = DatabaseHost::factory()->create();
    $response = $this->putJson(route('api.admin.database-hosts.update', ['databaseHost' => $host->id]), ['name' => '', 'host' => '127.0.0.1', 'port' => 'not-a-port', 'username' => 'updateduser']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    $nameError = $errors->firstWhere('meta.source_field', 'name');
    expect($nameError)->not->toBeNull('Expected a validation error for the [name] field.');
    $portError = $errors->firstWhere('meta.source_field', 'port');
    expect($portError)->not->toBeNull('Expected a validation error for the [port] field.');
});
test('update database host encrypts password', function (): void {
    mockDynamicConnection();
    $host = DatabaseHost::factory()->create();
    $response = $this->putJson(route('api.admin.database-hosts.update', ['databaseHost' => $host->id]), ['name' => $host->name, 'host' => '127.0.0.1', 'port' => 3306, 'username' => $host->username, 'password' => 'a-brand-new-password']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonMissing(['password' => 'a-brand-new-password']);

    expect(Crypt::decrypt($host->fresh()->password))->toBe('a-brand-new-password');
});
test('delete database host', function (): void {
    $host = DatabaseHost::factory()->create();
    $this->assertDatabaseHas('database_hosts', ['id' => $host->id]);
    $response = $this->delete(route('api.admin.database-hosts.delete', ['databaseHost' => $host->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('database_hosts', ['id' => $host->id]);
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $host = DatabaseHost::factory()->create();
    $response = $this->{$method}(route($routeName, ['databaseHost' => $host->id]));
    $this->assertAccessDeniedJson($response);
})->with('databaseHostEndpointsDataProvider');
/** No-op DynamicDatabaseConnection and point "dynamic" at the test database so the live "SELECT 1" succeeds. */
function mockDynamicConnection(): FakeDynamicDatabaseConnection
{
    return (function (): FakeDynamicDatabaseConnection {
        config()->set('database.connections.dynamic', config('database.connections.mysql'));
        $fake = new FakeDynamicDatabaseConnection();
        $this->app->instance(DynamicDatabaseConnection::class, $fake);

        return $fake;
    })->call(pterodactylTestCase());
}
