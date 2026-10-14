<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Databases\CreateDatabaseHostTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PDOException;
use Pterodactyl\Contracts\Databases\CreatesDatabaseHosts;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDynamicDatabaseConnection;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    // The live connection test runs its query against the test database.
    config()->set('database.connections.dynamic', config('database.connections.mysql'));
    $this->app->instance(DynamicDatabaseConnection::class, $this->dynamic = new FakeDynamicDatabaseConnection);
});

/** @return array<string, int|string|null> */
function host(array $overrides = []): array
{
    return [...['name' => 'Direct', 'host' => 'db.example.com', 'port' => 3306, 'username' => 'direct', 'password' => 'secret'], ...$overrides];
}

test('database hosts created without the http api meet the same rules', function (array $input, string $field): void {
    $count = DatabaseHost::query()->count();

    try {
        $this->app->make(CreatesDatabaseHosts::class)->create(host($input));
        $this->fail('The database host was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect(DatabaseHost::query()->count())->toBe($count)
        ->and($this->dynamic->setCalls)->toBe([]);
})->with([
    'a missing name' => [['name' => null], 'name'],
    'a name that is too long' => [['name' => str_repeat('a', 192)], 'name'],
    'a host with a space' => [['host' => 'db example.com'], 'host'],
    'a missing port' => [['port' => null], 'port'],
    'a port that is not a number' => [['port' => 'mysql'], 'port'],
    'a port of zero' => [['port' => 0], 'port'],
    'a port above 65535' => [['port' => 65536], 'port'],
    'a username that is too long' => [['username' => str_repeat('a', 33)], 'username'],
    'a password that is not a string' => [['password' => ['secret']], 'password'],
    'an unknown node' => [['node_id' => 0], 'node_id'],
    'a node that is not a number' => [['node_id' => 'local'], 'node_id'],
]);

test('values are coerced the way laravel validates them', function (): void {
    $node = Node::factory()->for(Location::factory())->create();

    $host = $this->app->make(CreatesDatabaseHosts::class)->create(host(['port' => '3307', 'node_id' => (string) $node->id, 'username' => '1024']));

    $host->refresh();
    expect($host->port)->toBe(3307)
        ->and($host->node_id)->toBe($node->id)
        ->and($host->username)->toBe('1024');
});

test('a host keeps its password encrypted, and one given no node is global', function (): void {
    $host = $this->app->make(CreatesDatabaseHosts::class)->create(host());

    $host->refresh();
    expect(Crypt::decrypt($host->password))->toBe('secret')
        ->and($host->node_id)->toBeNull()
        ->and($host->max_databases)->toBeNull();

    $tested = $this->dynamic->setCalls[0]['host'];
    expect($tested)->toBeInstanceOf(DatabaseHost::class)
        ->and($tested->host)->toBe('db.example.com')
        ->and($tested->username)->toBe('direct');
});

test('a host given a blank password or none stores none', function (array $input): void {
    $host = $this->app->make(CreatesDatabaseHosts::class)->create($input);

    expect(Crypt::decrypt($host->refresh()->password))->toBeNull();
})->with([
    'a null password' => [host(['password' => null])],
    'a blank password' => [host(['password' => ''])],
    'no password' => [array_diff_key(host(), ['password' => true])],
]);

test('a host that refuses the connection is not saved', function (): void {
    $this->dynamic->throwOnSet = new PDOException('connection refused');
    $count = DatabaseHost::query()->count();

    expect(fn () => $this->app->make(CreatesDatabaseHosts::class)->create(host()))
        ->toThrow(PDOException::class, 'connection refused');
    expect(DatabaseHost::query()->count())->toBe($count);
});

test('extension field values that no request validated are refused', function (): void {
    expect(fn () => $this->app->make(CreatesDatabaseHosts::class)->create(host(['extensions' => ['billing' => ['plan' => 'pro']]])))
        ->toThrow(InvalidArgumentException::class, 'must come from a request that validated them');
});
