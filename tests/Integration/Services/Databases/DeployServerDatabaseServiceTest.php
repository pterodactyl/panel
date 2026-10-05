<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Databases\DeployServerDatabaseServiceTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
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
test('error is thrown if database name is empty', function (array $data) {
    $server = $this->createServerModel(['database_limit' => 5]);
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Expected a non-empty database name.');
    getService()->deploy($server, $data);
})->with('invalidDataProvider');
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
