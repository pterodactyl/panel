<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Databases\RotateDatabasePasswordTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Contracts\Databases\RotatesDatabasePasswords;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDatabaseHostGateway;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
/**
 * Setup tests.
 */
beforeEach(function () {
    $this->app->instance(DatabaseHostGateway::class, $this->fake = new FakeDatabaseHostGateway());
});
test('database password can be rotated', function () {
    $server = $this->createServerModel();
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    $database = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id, 'password' => encrypt('original')]);
    $other = Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id, 'password' => encrypt('unchanged')]);
    /** @var FakeDatabaseHostGateway $fake */
    $fake = $this->fake;
    $expectedMaxConnections = $database->max_connections;
    $response = getService()->rotate($database);
    // The new password is returned, set on the host, and stored.
    expect(mb_strlen($response))->toBe(24);
    expect(decrypt($database->refresh()->password))->toBe($response);
    // Other databases are untouched.
    expect(decrypt($other->refresh()->password))->toBe('unchanged');

    $fake->assertDroppedUser($database->username, $database->remote);
    expect($fake->createdUsers)->toHaveCount(1);
    expect($fake->createdUsers[0]['username'])->toBe($database->username);
    expect($fake->createdUsers[0]['remote'])->toBe($database->remote);
    expect($fake->createdUsers[0]['password'])->toBe($response);
    expect($fake->createdUsers[0]['maxConnections'])->toBe($expectedMaxConnections);
    $fake->assertAssigned($database->database, $database->username, $database->remote);
    $fake->assertFlushed(1);
});
function getService(): RotatesDatabasePasswords
{
    return (function () {
        return $this->app->make(RotatesDatabasePasswords::class);
    })->call(pterodactylTestCase());
}
