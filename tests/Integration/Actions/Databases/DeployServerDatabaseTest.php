<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Databases\DeployServerDatabaseTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Databases\DeploysServerDatabases;
use Pterodactyl\Exceptions\Service\Database\NoSuitableDatabaseHostException;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDatabaseHostGateway;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
/**
 * Setup tests.
 */
beforeEach(function () {
    config()->set('pterodactyl.client_features.databases.enabled', true);
    $this->app->instance(DatabaseHostGateway::class, $this->gateway = new FakeDatabaseHostGateway());
});
/**
 * Ensure we reset the config to the expected value.
 */
afterEach(function () {
    config()->set('pterodactyl.client_features.databases.allow_random', true);
    Database::query()->delete();
    DatabaseHost::query()->delete();
});
dataset('invalidDataProvider', function () {
    return [[['remote' => '%']], [['database' => null, 'remote' => '%']], [['database' => '', 'remote' => '%']], [['database' => '']], [['database' => '', 'remote' => '']]];
});
test('validation fails if database name is empty', function (array $data) {
    $server = $this->createServerModel(['database_limit' => 5]);

    try {
        getService()->deploy($server, $data);
        $this->fail('The database was deployed.');
    } catch (ValidationException $validationException) {
        expect($validationException->errors())->toHaveKey('database');
    }
})->with('invalidDataProvider');
test('databases deployed without the http api meet the same rules', function (array $input, string $field) {
    $server = $this->createServerModel(['database_limit' => 5]);
    DatabaseHost::factory()->create(['node_id' => $server->node_id]);

    try {
        getService()->deploy($server, [...['database' => 'direct', 'remote' => '%'], ...$input]);
        $this->fail('The database was deployed.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($server->databases()->exists())->toBeFalse()
        ->and($this->gateway->calls)->toBe([]);
})->with([
    'a name that is too short' => [['database' => 'ab'], 'database'],
    'a name with a dot' => [['database' => 'my.database'], 'database'],
    'a name that is too long' => [['database' => str_repeat('a', 49)], 'database'],
    'a missing remote' => [['remote' => null], 'remote'],
    'a remote with a space' => [['remote' => '10.0.0. %'], 'remote'],
    'an unknown host' => [['database_host_id' => 0], 'database_host_id'],
    'a negative connection limit' => [['max_connections' => -1], 'max_connections'],
]);
test('values are coerced the way laravel validates them, and a given host is used', function () {
    $server = $this->createServerModel(['database_limit' => 5]);
    DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);

    $response = getService()->deploy($server, ['database' => 'chosen', 'remote' => '%', 'database_host_id' => (string) $host->id, 'max_connections' => '15']);

    $response->refresh();
    expect($response->database_host_id)->toBe($host->id)
        ->and($response->database)->toBe("s{$server->id}_chosen")
        ->and($response->max_connections)->toBe(15);
});
test('a database deployed without a connection limit has none', function () {
    $server = $this->createServerModel(['database_limit' => 5]);
    DatabaseHost::factory()->create(['node_id' => $server->node_id]);

    $response = getService()->deploy($server, ['database' => 'unlimited', 'remote' => '%']);

    expect($response->refresh()->max_connections)->toBeNull();
});
test('error is thrown if no database hosts exist on node', function () {
    $server = $this->createServerModel(['database_limit' => 5]);
    $node = Node::factory()->create(['location_id' => $server->location->id]);
    DatabaseHost::factory()->create(['node_id' => $node->id]);
    config()->set('pterodactyl.client_features.databases.allow_random', false);
    $this->expectException(NoSuitableDatabaseHostException::class);
    getService()->deploy($server, ['database' => 'something', 'remote' => '%']);
});
test('error is thrown if no database hosts exist on system', function () {
    $server = $this->createServerModel(['database_limit' => 5]);
    $this->expectException(NoSuitableDatabaseHostException::class);
    getService()->deploy($server, ['database' => 'something', 'remote' => '%']);
});
test('database host on same node is preferred', function () {
    $server = $this->createServerModel(['database_limit' => 5]);
    $node = Node::factory()->create(['location_id' => $server->location->id]);
    DatabaseHost::factory()->create(['node_id' => $node->id]);
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    $response = getService()->deploy($server, ['database' => 'something', 'remote' => '%']);
    expect($response)->toBeInstanceOf(Database::class);
    expect($response->database_host_id)->toBe($host->id);
    expect($response->database)->toBe("s{$server->id}_something");
    $this->assertDatabaseHas('databases', ['id' => $response->id]);
    /** @var FakeDatabaseHostGateway $gateway */
    $gateway = $this->gateway;
    $gateway->assertCreated("s{$server->id}_something");
    $gateway->assertFlushed(1);
});
test('database host is selected if no suitable host exists on same node', function () {
    $server = $this->createServerModel(['database_limit' => 5]);
    $node = Node::factory()->create(['location_id' => $server->location->id]);
    $host = DatabaseHost::factory()->create(['node_id' => $node->id]);
    $response = getService()->deploy($server, ['database' => 'something', 'remote' => '%']);
    expect($response)->toBeInstanceOf(Database::class);
    expect($response->database_host_id)->toBe($host->id);
    expect($response->database)->toBe("s{$server->id}_something");
    $this->assertDatabaseHas('databases', ['id' => $response->id]);
    /** @var FakeDatabaseHostGateway $gateway */
    $gateway = $this->gateway;
    $gateway->assertCreated("s{$server->id}_something");
    $gateway->assertFlushed(1);
});
function getService(): DeploysServerDatabases
{
    return (function () {
        return $this->app->make(DeploysServerDatabases::class);
    })->call(pterodactylTestCase());
}
