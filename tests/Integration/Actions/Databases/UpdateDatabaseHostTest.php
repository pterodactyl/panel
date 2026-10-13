<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Databases\UpdateDatabaseHostTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use PDOException;
use Pterodactyl\Contracts\Databases\UpdatesDatabaseHosts;
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
function fields(DatabaseHost $host, array $overrides = []): array
{
    return [...['name' => $host->name, 'host' => $host->host, 'port' => $host->port, 'username' => $host->username, 'node_id' => $host->node_id], ...$overrides];
}

test('a blank password keeps the stored one, and a new one is encrypted', function (): void {
    $host = DatabaseHost::factory()->create(['password' => Crypt::encrypt('stored')]);
    $hosts = $this->app->make(UpdatesDatabaseHosts::class);

    $hosts->update($host, fields($host, ['password' => null]));
    $hosts->update($host, fields($host, ['password' => '']));
    $hosts->update($host, fields($host));
    expect(Crypt::decrypt($host->refresh()->password))->toBe('stored');

    $hosts->update($host, fields($host, ['password' => 'replaced']));
    expect(Crypt::decrypt($host->refresh()->password))->toBe('replaced');
});

test('an update replaces the fields it is given, and a host given no node becomes global', function (): void {
    $node = Node::factory()->for(Location::factory())->create();
    $host = DatabaseHost::factory()->create(['node_id' => $node->id]);
    $hosts = $this->app->make(UpdatesDatabaseHosts::class);

    $hosts->update($host, fields($host, ['name' => 'Renamed', 'port' => '3307', 'node_id' => (string) $node->id]));
    $host->refresh();
    expect($host->name)->toBe('Renamed')
        ->and($host->port)->toBe(3307)
        ->and($host->node_id)->toBe($node->id);

    $hosts->update($host, array_diff_key(fields($host), ['node_id' => true]));
    expect($host->refresh()->node_id)->toBeNull();
});

test('database hosts updated without the http api meet the same rules', function (array $input, string $field): void {
    $host = DatabaseHost::factory()->create()->refresh();
    $before = $host->getAttributes();

    try {
        $this->app->make(UpdatesDatabaseHosts::class)->update($host, fields($host, $input));
        $this->fail('The database host was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($host->fresh()->getAttributes())->toBe($before)
        ->and($this->dynamic->setCalls)->toBe([]);
})->with([
    'a blank name' => [['name' => ''], 'name'],
    'a host with a slash' => [['host' => 'db/example'], 'host'],
    'a port above 65535' => [['port' => '65536'], 'port'],
    'a missing username' => [['username' => null], 'username'],
    'an unknown node' => [['node_id' => 0], 'node_id'],
]);

test('a host that refuses the new credentials keeps the stored ones', function (): void {
    $host = DatabaseHost::factory()->create()->refresh();
    $before = $host->getAttributes();
    $this->dynamic->throwOnSet = new PDOException('access denied');

    expect(fn () => $this->app->make(UpdatesDatabaseHosts::class)->update($host, fields($host, ['username' => 'other', 'password' => 'wrong'])))
        ->toThrow(PDOException::class, 'access denied');
    expect($host->fresh()->getAttributes())->toBe($before);
});
