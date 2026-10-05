<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\ClientControllerTest;

use Closure;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Permission;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Ramsey\Uuid\Uuid;

uses(\Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase::class);
dataset('filterTypeDataProvider', function () {
    return [['admin'], ['admin-all']];
});
test('only logged in users servers are returned', function () {
    /** @var User[] $users */
    $users = User::factory()->times(3)->create();
    /** @var Server[] $servers */
    $servers = [$this->createServerModel(['user_id' => $users[0]->id]), $this->createServerModel(['user_id' => $users[1]->id]), $this->createServerModel(['user_id' => $users[2]->id])];
    $response = $this->actingAs($users[0])->getJson('/api/client');
    $response->assertOk();
    $response->assertJsonPath('object', 'list');
    $response->assertJsonPath('data.0.object', Server::RESOURCE_NAME);
    $response->assertJsonPath('data.0.attributes.identifier', $servers[0]->uuidShort);
    $response->assertJsonPath('data.0.attributes.server_owner', true);
    $response->assertJsonPath('meta.pagination.total', 1);
    $response->assertJsonPath('meta.pagination.per_page', 50);
});
test('servers are filtered using name and uuid information', function () {
    /** @var User[] $users */
    $users = User::factory()->times(2)->create();
    $users[0]->update(['root_admin' => true]);
    /** @var Server[] $servers */
    $servers = [$this->createServerModel(['user_id' => $users[0]->id, 'name' => 'Julia']), $this->createServerModel(['user_id' => $users[1]->id, 'uuidShort' => '12121212', 'name' => 'Janice']), $this->createServerModel(['user_id' => $users[1]->id, 'uuid' => Uuid::uuid4()->toString(), 'external_id' => 'ext123', 'name' => 'Julia']), $this->createServerModel(['user_id' => $users[1]->id, 'uuid' => Uuid::uuid4()->toString(), 'name' => 'Jennifer'])];
    $this->actingAs($users[1])->getJson('/api/client?filter[*]=Julia')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.identifier', $servers[2]->uuidShort);
    $this->actingAs($users[1])->getJson('/api/client?filter[*]=ext123')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.identifier', $servers[2]->uuidShort);
    $this->actingAs($users[1])->getJson('/api/client?filter[*]=12121212')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.identifier', $servers[1]->uuidShort);
    $this->actingAs($users[1])->getJson("/api/client?filter[*]={$servers[2]->uuidShort}")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.identifier', $servers[2]->uuidShort);
    $this->actingAs($users[1])->getJson('/api/client?filter[*]=88788878-abcd')->assertOk()->assertJsonCount(0, 'data');
    $this->actingAs($users[0])->getJson('/api/client?filter[*]=Julia&type=admin-all')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.attributes.identifier', $servers[0]->uuidShort)->assertJsonPath('data.1.attributes.identifier', $servers[2]->uuidShort);
});
test('servers are filtered using allocation information', function () {
    /** @var User $user */
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount();
    $server2 = $this->createServerModel(['user_id' => $user->id, 'node_id' => $server->node_id]);
    $allocation = Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => $server->id, 'ip' => '192.168.1.1', 'port' => 25565]);
    $allocation2 = Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => $server2->id, 'ip' => '192.168.1.1', 'port' => 25570]);
    $server->update(['allocation_id' => $allocation->id]);
    $server2->update(['allocation_id' => $allocation2->id]);
    $server->refresh();
    $server2->refresh();
    $this->actingAs($user)->getJson('/api/client?filter[*]=192.168.1.1')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.attributes.identifier', $server->uuidShort)->assertJsonPath('data.1.attributes.identifier', $server2->uuidShort);
    $this->actingAs($user)->getJson('/api/client?filter[*]=192.168.1.1:25565')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.identifier', $server->uuidShort);
    $this->actingAs($user)->getJson('/api/client?filter[*]=:25570')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.identifier', $server2->uuidShort);
    $this->actingAs($user)->getJson('/api/client?filter[*]=:255')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.attributes.identifier', $server->uuidShort)->assertJsonPath('data.1.attributes.identifier', $server2->uuidShort);
});
test('servers user is a subuser of are returned', function () {
    /** @var User[] $users */
    $users = User::factory()->times(3)->create();
    $servers = [$this->createServerModel(['user_id' => $users[0]->id]), $this->createServerModel(['user_id' => $users[1]->id]), $this->createServerModel(['user_id' => $users[2]->id])];
    // Set user 0 as a subuser of server 1. Thus, we should get two servers
    // back in the response when making the API call as user 0.
    Subuser::query()->create(['user_id' => $users[0]->id, 'server_id' => $servers[1]->id, 'permissions' => [Permissions::WebsocketConnect->value]]);
    $response = $this->actingAs($users[0])->getJson('/api/client');
    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.attributes.server_owner', true);
    $response->assertJsonPath('data.0.attributes.identifier', $servers[0]->uuidShort);
    $response->assertJsonPath('data.1.attributes.server_owner', false);
    $response->assertJsonPath('data.1.attributes.identifier', $servers[1]->uuidShort);
});
test('filter only owner servers', function () {
    /** @var User[] $users */
    $users = User::factory()->times(3)->create();
    $servers = [$this->createServerModel(['user_id' => $users[0]->id]), $this->createServerModel(['user_id' => $users[1]->id]), $this->createServerModel(['user_id' => $users[2]->id])];
    // Set user 0 as a subuser of server 1. Thus, we should get two servers
    // back in the response when making the API call as user 0.
    Subuser::query()->create(['user_id' => $users[0]->id, 'server_id' => $servers[1]->id, 'permissions' => [Permissions::WebsocketConnect->value]]);
    $response = $this->actingAs($users[0])->getJson('/api/client?type=owner');
    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.attributes.server_owner', true);
    $response->assertJsonPath('data.0.attributes.identifier', $servers[0]->uuidShort);
});
test('permissions are returned', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/api/client/permissions')->assertOk()->assertJson(['object' => 'system_permissions', 'attributes' => ['permissions' => Permission::permissions()->toArray()]]);
});
test('only admin level servers are returned', function () {
    /** @var User[] $users */
    $users = User::factory()->times(4)->create();
    $users[0]->update(['root_admin' => true]);
    $servers = [$this->createServerModel(['user_id' => $users[0]->id]), $this->createServerModel(['user_id' => $users[1]->id]), $this->createServerModel(['user_id' => $users[2]->id]), $this->createServerModel(['user_id' => $users[3]->id])];
    Subuser::query()->create(['user_id' => $users[0]->id, 'server_id' => $servers[1]->id, 'permissions' => [Permissions::WebsocketConnect->value]]);
    // Only servers 2 & 3 (0 indexed) should be returned by the API at this point. The user making
    // the request is the owner of server 0, and a subuser of server 1, so they should be excluded.
    $response = $this->actingAs($users[0])->getJson('/api/client?type=admin');
    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.attributes.server_owner', false);
    $response->assertJsonPath('data.0.attributes.identifier', $servers[2]->uuidShort);
    $response->assertJsonPath('data.1.attributes.server_owner', false);
    $response->assertJsonPath('data.1.attributes.identifier', $servers[3]->uuidShort);
});
test('all servers are returned to admin', function () {
    /** @var User[] $users */
    $users = User::factory()->times(4)->create();
    $users[0]->update(['root_admin' => true]);
    $servers = [$this->createServerModel(['user_id' => $users[0]->id]), $this->createServerModel(['user_id' => $users[1]->id]), $this->createServerModel(['user_id' => $users[2]->id]), $this->createServerModel(['user_id' => $users[3]->id])];
    Subuser::query()->create(['user_id' => $users[0]->id, 'server_id' => $servers[1]->id, 'permissions' => [Permissions::WebsocketConnect->value]]);
    // All servers should be returned.
    $response = $this->actingAs($users[0])->getJson('/api/client?type=admin-all');
    $response->assertOk();
    $response->assertJsonCount(4, 'data');
});
test('no servers are returned if admin filter is passed by regular user', function (string $type) {
    /** @var User[] $users */
    $users = User::factory()->times(3)->create();
    $this->createServerModel(['user_id' => $users[0]->id]);
    $this->createServerModel(['user_id' => $users[1]->id]);
    $this->createServerModel(['user_id' => $users[2]->id]);
    $response = $this->actingAs($users[0])->getJson('/api/client?type='.$type);
    $response->assertOk();
    $response->assertJsonCount(0, 'data');
})->with('filterTypeDataProvider');
test('only primary allocation is returned to subuser', function () {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $server->allocation->notes = 'Test notes';
    $server->allocation->save();
    Allocation::factory()->times(2)->create(['node_id' => $server->node_id, 'server_id' => $server->id]);
    $server->refresh();
    $response = $this->actingAs($user)->getJson('/api/client');
    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.attributes.server_owner', false);
    $response->assertJsonPath('data.0.attributes.uuid', $server->uuid);
    $response->assertJsonCount(1, 'data.0.attributes.relationships.allocations.data');
    $response->assertJsonPath('data.0.attributes.relationships.allocations.data.0.attributes.id', $server->allocation->id);
    $response->assertJsonPath('data.0.attributes.relationships.allocations.data.0.attributes.notes', null);
});

function measuredReads(Closure $request): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    try {
        $request()->assertOk();

        return count(array_filter(DB::getQueryLog(), fn (array $query): bool => str_starts_with(mb_strtolower($query['query']), 'select')));
    } finally {
        DB::disableQueryLog();
    }
}

test('client list batches dependencies and preserves each server override', function (bool $subuser) {
    $viewer = User::factory()->create();
    $servers = [];
    for ($i = 0; $i < 10; $i++) {
        $server = $this->createServerModel($subuser ? [] : ['owner_id' => $viewer->id]);
        if ($subuser) {
            Subuser::factory()->create(['server_id' => $server->id, 'user_id' => $viewer->id, 'permissions' => [Permissions::StartupRead->value, Permissions::AllocationRead->value, Permissions::UserRead->value]]);
        }
        $variable = $server->egg->variables()->firstOrFail();
        $variable->update(['user_viewable' => true]);
        ServerVariable::query()->create(['server_id' => $server->id, 'variable_id' => $variable->id, 'variable_value' => 'override-'.$i]);
        $server->update(['startup' => '{{'.$variable->env_variable.'}}']);
        $servers[] = $server;
    }
    $this->actingAs($viewer);
    $request = fn (int $limit) => $this->getJson('/api/client?include=subusers&per_page='.$limit);
    $request(1)->assertOk();
    $one = measuredReads(fn () => $request(1));
    $ten = measuredReads(fn () => $request(10));
    expect($ten)->toBe($one);
    $response = $request(10)->assertOk()->assertJsonCount(10, 'data');
    foreach ($servers as $i => $server) {
        $row = collect($response->json('data'))->firstWhere('attributes.uuid', $server->uuid);
        expect($row['attributes']['invocation'])->toBe('override-'.$i);
        expect(collect($row['attributes']['relationships']['variables']['data'])->pluck('attributes.server_value'))->toContain('override-'.$i);
    }
})->with([false, true]);

test('client child lists have bounded reads', function (string $resource) {
    [$user, $server] = $this->generateTestAccount();
    $host = DatabaseHost::factory()->create();
    $add = function () use ($resource, $server, $host): void {
        match ($resource) {
            'network/allocations' => Allocation::factory()->create(['server_id' => $server->id, 'node_id' => $server->node_id]),
            'users' => Subuser::factory()->create(['server_id' => $server->id]),
            default => Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]),
        };
    };
    if ($resource !== 'network/allocations') {
        $add();
    }
    $this->actingAs($user);
    $request = fn () => $this->getJson('/api/client/servers/'.$server->uuid.'/'.$resource);
    $request()->assertOk();
    $one = measuredReads($request);
    for ($i = 0; $i < 9; $i++) {
        $add();
    }
    expect(measuredReads($request))->toBe($one);
    $request()->assertOk()->assertJsonCount(10, 'data');
})->with(['network/allocations', 'users', 'databases', 'databases?include=password']);
