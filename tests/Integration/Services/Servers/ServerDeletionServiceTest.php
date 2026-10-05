<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Servers\ServerDeletionServiceTest;

use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Contracts\Servers\DeletesServers;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;
use Pterodactyl\Tests\Support\Fakes\FakeDatabaseHostGateway;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
/**
 * Stub out services that we don't want to test in here.
 */
beforeEach(function () {
    $this->defaultLogger = config('logging.default');
    // There will be some log calls during this test, don't actually write to the disk.
    config()->set('logging.default', 'null');
    $this->daemonServerRepository = new FakeDaemonServer;
    $this->app->instance(DatabaseHostGateway::class, $this->gateway = new FakeDatabaseHostGateway());
});
/**
 * Reset the log driver.
 */
afterEach(function () {
    config()->set('logging.default', $this->defaultLogger);
    $this->defaultLogger = null;
});
test('regular delete fails if wings returns error', function () {
    $server = $this->createServerModel();
    $this->daemonServerRepository->throwable = new DaemonConnectionException(Http::failedRequest([], 200));
    try {
        getService()->delete($server);
        $this->fail('Expected DaemonConnectionException to be thrown.');
    } catch (DaemonConnectionException) {
    }
    $this->daemonServerRepository->assertDeleted();
    $this->assertDatabaseHas('servers', ['id' => $server->id]);
});
test('regular delete ignores404 from wings', function () {
    $server = $this->createServerModel();
    $this->daemonServerRepository->throwable = new DaemonConnectionException(Http::failedRequest([], 404));
    getService()->delete($server);
    $this->daemonServerRepository->assertDeleted();
    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
});
test('force delete ignores exception from wings', function () {
    $server = $this->createServerModel();
    $this->daemonServerRepository->throwable = new DaemonConnectionException(Http::failedRequest([], 500));
    getService()->withForce()->delete($server);
    $this->daemonServerRepository->assertDeleted();
    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
});
test('exception while deleting stops process', function () {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    /** @var Database $db */
    $db = Database::factory()->create(['database_host_id' => $host->id, 'server_id' => $server->id]);
    $server->refresh();
    $this->gateway->throwOn['dropDatabase'] = new Exception();
    try {
        getService()->delete($server);
        $this->fail('Expected Exception to be thrown.');
    } catch (Exception) {
    }
    $this->daemonServerRepository->assertDeleted();
    $this->assertDatabaseHas('servers', ['id' => $server->id]);
    $this->assertDatabaseHas('databases', ['id' => $db->id]);
});
test('exception while deleting databases does not abort if force deleted', function () {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create();
    /** @var Database $db */
    $db = Database::factory()->create(['database_host_id' => $host->id, 'server_id' => $server->id]);
    $server->refresh();
    $this->gateway->throwOn['dropDatabase'] = new Exception();
    getService()->withForce(true)->delete($server);
    $this->daemonServerRepository->assertDeleted();
    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    $this->assertDatabaseMissing('databases', ['id' => $db->id]);
});
function getService(): DeletesServers
{
    return (function () {
        return $this->app->make(DeletesServers::class);
    })->call(pterodactylTestCase());
}

test('deleting a server reads database hosts once for all databases', function () {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    Database::factory()->count(10)->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    /** @var FakeDatabaseHostGateway $gateway */
    $gateway = $this->gateway;
    DB::flushQueryLog();
    DB::enableQueryLog();
    try {
        $this->app->make(DeletesServers::class)->delete($server);
        $reads = array_filter(DB::getQueryLog(), fn (array $query): bool => str_starts_with(mb_strtolower($query['query']), 'select') && str_contains($query['query'], 'from `database_hosts`'));
        expect($reads)->toHaveCount(1);
    } finally {
        DB::disableQueryLog();
    }
    expect($gateway->count('dropDatabase'))->toBe(10);
    expect($gateway->count('dropUser'))->toBe(10);
    expect($gateway->count('flush'))->toBe(10);
    $this->daemonServerRepository->assertDeleted();
    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    $this->assertDatabaseMissing('databases', ['server_id' => $server->id]);
});

test('a deleted server is reported to operation listeners by uuid only', function () {
    Event::fake([OperationCompleted::class]);
    $server = $this->createServerModel();
    $kept = $this->createServerModel();
    $this->daemonServerRepository->throwable = new DaemonConnectionException(Http::failedRequest([], 500));
    try {
        getService()->delete($kept);
    } catch (DaemonConnectionException) {
    }
    $this->daemonServerRepository->throwable = null;

    getService()->delete($server);

    Event::assertDispatchedTimes(OperationCompleted::class, 1);
    Event::assertDispatched(OperationCompleted::class, fn (OperationCompleted $event): bool => $event->operation === 'delete'
        && $event->successful
        && $event->serverUuid === $server->uuid
        && $event->resourceUuid === $server->uuid);
});
