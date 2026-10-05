<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Database\DatabaseControllerTest;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDatabaseHostGateway;

uses(ClientApiIntegrationTestCase::class);
test('databases can be listed', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::DatabaseRead->value]);
    $host = DatabaseHost::factory()->create();
    $databases = Database::factory()->times(2)->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    // A database on a different server must not be included.
    Database::factory()->create(['server_id' => $this->createServerModel()->id, 'database_host_id' => $host->id]);
    $response = $this->actingAs($user)->getJson($this->link($server, '/databases'))->assertOk()->assertJsonPath('object', 'list')->assertJsonCount(2, 'data')->assertJsonPath('data.0.object', 'server_database');
    $names = collect($response->json('data'))->pluck('attributes.name');
    expect($names->toArray())->toEqualCanonicalizing($databases->pluck('database')->toArray());
});
test('database can be created', function () {
    $user = User::factory()->create();
    $server = $this->createServerModel(['owner_id' => $user->id, 'database_limit' => 2]);
    $host = DatabaseHost::factory()->create();
    $this->app->instance(DatabaseHostGateway::class, $gateway = new FakeDatabaseHostGateway());
    $this->actingAs($user)->postJson($this->link($server, '/databases'), ['database' => 'testdb2', 'remote' => '%'])->assertOk()->assertJsonPath('object', 'server_database')->assertJsonPath('attributes.name', "s{$server->id}_testdb2");
    $database = Database::query()->where('server_id', $server->id)->firstOrFail();
    expect($database->database)->toBe("s{$server->id}_testdb2");
    $gateway->assertCreated("s{$server->id}_testdb2");
    $gateway->assertFlushed(1);
});
test('database cannot be created past the server limit', function () {
    $user = User::factory()->create();
    $server = $this->createServerModel(['owner_id' => $user->id, 'database_limit' => 1]);
    $host = DatabaseHost::factory()->create();
    Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);
    $this->actingAs($user)->postJson($this->link($server, '/databases'), ['database' => 'overlimit', 'remote' => '%'])->assertBadRequest();
});
test('endpoints require permissions', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ControlConsole->value]);
    $this->getJson($this->link($server, '/databases'))->assertUnauthorized();
    $this->actingAs($user);
    $this->getJson($this->link($server, '/databases'))->assertForbidden();
    $this->postJson($this->link($server, '/databases'), ['database' => 'testdb', 'remote' => '%'])->assertForbidden();
});
