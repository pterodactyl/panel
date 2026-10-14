<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Databases\CreateDatabaseTest;

use BadMethodCallException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Pterodactyl\Contracts\Databases\CreatesDatabases;
use Pterodactyl\Exceptions\Repository\DuplicateDatabaseNameException;
use Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;
use Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Pterodactyl\Support\DatabaseName;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDatabaseHostGateway;
use Pterodactyl\Tests\Support\Fakes\FakeDynamicDatabaseConnection;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
/**
 * Setup tests.
 */
beforeEach(function (): void {
    config()->set('pterodactyl.client_features.databases.enabled', true);
    $this->app->instance(DatabaseHostGateway::class, $this->fake = new FakeDatabaseHostGateway());
});
dataset('invalidDataDataProvider', fn (): array => [[[]], [['database' => '']], [['database' => 'something']], [['database' => 's_something']], [['database' => 's12s_something']], [['database' => 's12something']]]);
test('unique database name is generated correctly', function (): void {
    expect(DatabaseName::generateUnique('example', 1))->toBe('s1_example');
    expect(DatabaseName::generateUnique('something_else', 123))->toBe('s123_something_else');
    expect(DatabaseName::generateUnique(str_repeat('a', 100), 123))->toBe('s123_'.str_repeat('a', 43));
});
test('exception is thrown if client databases are not enabled', function (): void {
    config()->set('pterodactyl.client_features.databases.enabled', false);
    $this->expectException(DatabaseClientFeatureNotEnabledException::class);
    $server = $this->createServerModel();
    getService()->create($server, []);
});
test('database cannot be created if server has reached limit', function (): void {
    $server = $this->createServerModel(['database_limit' => 2]);
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    Database::factory()->times(2)->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $this->expectException(TooManyDatabasesException::class);
    getService()->create($server, []);
});
test('empty database name or invalid name fails validation', function (array $data): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);

    try {
        getService()->create($server, [...['remote' => '%', 'database_host_id' => $host->id], ...$data]);
        $this->fail('The database was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe(['database']);
    }

    expect($this->fake->calls)->toBe([]);
})->with('invalidDataDataProvider');
test('creating database with identical name triggers an exception', function (): void {
    $server = $this->createServerModel();
    $name = DatabaseName::generateUnique('soemthing', $server->id);
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    $host2 = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    Database::factory()->create(['database' => $name, 'database_host_id' => $host->id, 'server_id' => $server->id]);
    // Try to create a database with the same name as a database on a different host. We expect
    // this to fail since we don't account for the specific host when checking uniqueness.
    try {
        getService()->create($server, ['database' => $name, 'remote' => '%', 'database_host_id' => $host2->id]);
        $this->fail('Expected DuplicateDatabaseNameException to be thrown.');
    } catch (DuplicateDatabaseNameException $duplicateDatabaseNameException) {
        expect($duplicateDatabaseNameException->getMessage())->toBe('A database with that name already exists for this server.');
    }

    $this->assertDatabaseMissing('databases', ['database' => $name, 'database_host_id' => $host2->id]);
});
test('server database can be created', function (): void {
    $server = $this->createServerModel();
    $name = DatabaseName::generateUnique('soemthing', $server->id);
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    /** @var FakeDatabaseHostGateway $fake */
    $fake = $this->fake;
    $response = getService()->create($server, ['remote' => '%', 'database' => $name, 'database_host_id' => $host->id]);
    expect($response)->toBeInstanceOf(Database::class);
    expect($server->id)->toBe($response->server_id);
    $this->assertDatabaseHas('databases', ['server_id' => $server->id, 'id' => $response->id]);

    $fake->assertCreated($name);
    expect($fake->count('createDatabase'))->toBe(1);
    expect($fake->createdUsers)->toHaveCount(1);
    expect($fake->createdUsers[0]['username'])->toMatch('/^(u\d+_)(\w){10}$/');
    expect($fake->createdUsers[0]['remote'])->toBe('%');
    expect(mb_strlen($fake->createdUsers[0]['password']))->toBe(24);
    expect($fake->createdUsers[0]['maxConnections'])->toBeNull();

    $fake->assertAssigned($name, $fake->createdUsers[0]['username'], '%');
    $fake->assertFlushed(1);
    $this->assertActivityOn('server:database.create', $server, $response);
});
test('exception encountered while creating database attempts to cleanup', function (): void {
    $server = $this->createServerModel();
    $name = DatabaseName::generateUnique('soemthing', $server->id);
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    /** @var FakeDatabaseHostGateway $fake */
    $fake = $this->fake;
    $fake->throwOn['createDatabase'] = new BadMethodCallException();
    $fake->throwOn['dropUser'] = new InvalidArgumentException();
    expect(fn (): Database => getService()->create($server, ['remote' => '%', 'database' => $name, 'database_host_id' => $host->id]))
        ->toThrow(BadMethodCallException::class);
    $this->assertDatabaseMissing('databases', ['server_id' => $server->id]);
    $fake->assertDroppedDatabase($name);
    expect($fake->count('dropUser'))->toBe(1);
});
test('each failed compensation is reported and does not skip the remaining cleanup', function (): void {
    Exceptions::fake();
    $server = $this->createServerModel();
    $name = DatabaseName::generateUnique('cleanup', $server->id);
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    $fake = $this->fake;
    $primary = new BadMethodCallException('provisioning failed');
    $databaseCleanup = new InvalidArgumentException('database cleanup failed');
    $userCleanup = new InvalidArgumentException('user cleanup failed');
    $fake->throwOn = ['createUser' => $primary, 'dropDatabase' => $databaseCleanup, 'dropUser' => $userCleanup];

    expect(fn (): Database => getService()->create($server, ['remote' => '%', 'database' => $name, 'database_host_id' => $host->id]))
        ->toThrow($primary);

    $this->assertDatabaseMissing('databases', ['server_id' => $server->id, 'database' => $name]);
    expect($fake->count('dropDatabase'))->toBe(1);
    expect($fake->count('dropUser'))->toBe(1);

    $fake->assertFlushed(1);
    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => $exception === $databaseCleanup);
    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => $exception === $userCleanup);
});
test('host selection failure never mutates the previous database host', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    $dynamic = new FakeDynamicDatabaseConnection();
    $dynamic->throwOnSet = new InvalidArgumentException('host selection failed');

    $this->app->instance(DynamicDatabaseConnection::class, $dynamic);

    expect(fn (): Database => getService()->create($server, [
        'remote' => '%', 'database' => DatabaseName::generateUnique('selection', $server->id), 'database_host_id' => $host->id,
    ]))->toThrow(InvalidArgumentException::class, 'host selection failed');

    $this->assertDatabaseMissing('databases', ['server_id' => $server->id]);
    expect($this->fake->calls)->toBe([]);
});
test('databases created without the http api meet the same rules', function (array $input, string $field): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);

    try {
        getService()->create($server, [...['database' => DatabaseName::generateUnique('direct', $server->id), 'remote' => '%', 'database_host_id' => $host->id], ...$input]);
        $this->fail('The database was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    $this->assertDatabaseMissing('databases', ['server_id' => $server->id]);
    expect($this->fake->calls)->toBe([]);
})->with([
    'a name that is too long' => [['database' => 's1_'.str_repeat('a', 46)], 'database'],
    'a name with a space' => [['database' => 's1_my database'], 'database'],
    'a missing remote' => [['remote' => null], 'remote'],
    'a remote with a quote' => [['remote' => "%'"], 'remote'],
    'a missing host' => [['database_host_id' => null], 'database_host_id'],
    'an unknown host' => [['database_host_id' => 0], 'database_host_id'],
    'a host that is not a number' => [['database_host_id' => 'primary'], 'database_host_id'],
    'a negative connection limit' => [['max_connections' => -1], 'max_connections'],
    'a connection limit that is not a number' => [['max_connections' => 'many'], 'max_connections'],
]);
test('values are coerced the way laravel validates them', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);

    $database = getService()->create($server, ['database' => DatabaseName::generateUnique('coerced', $server->id), 'remote' => '%', 'database_host_id' => (string) $host->id, 'max_connections' => '10']);

    $database->refresh();
    expect($database->database_host_id)->toBe($host->id)
        ->and($database->max_connections)->toBe(10)
        ->and($this->fake->createdUsers[0]['maxConnections'])->toBe(10);
});
test('a database given no connection limit takes the column default, and one given null has none', function (): void {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    $input = ['remote' => '%', 'database_host_id' => $host->id];

    $default = getService()->create($server, [...$input, 'database' => DatabaseName::generateUnique('default', $server->id)]);
    $none = getService()->create($server, [...$input, 'database' => DatabaseName::generateUnique('none', $server->id), 'max_connections' => null]);

    expect($default->refresh()->max_connections)->toBe(0)
        ->and($none->refresh()->max_connections)->toBeNull();
});
function getService(): CreatesDatabases
{
    return (fn () => $this->app->make(CreatesDatabases::class))->call(pterodactylTestCase());
}
