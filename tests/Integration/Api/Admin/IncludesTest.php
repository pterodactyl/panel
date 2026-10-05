<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\IncludesTest;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;

use function pterodactylTestCase;

uses(\Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase::class);
test('server includes', function () {
    $server = createServerWithRelations();
    $includes = 'allocation,allocations,user,subusers,egg,variables,location,node,databases';
    $response = $this->getJson(route('api.admin.servers.view', ['server' => $server->id, 'include' => $includes]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['attributes' => ['relationships' => ['allocation' => ['object', 'attributes'], 'user' => ['object', 'attributes'], 'egg' => ['object', 'attributes'], 'location' => ['object', 'attributes'], 'node' => ['object', 'attributes'], 'allocations' => ['object', 'data'], 'subusers' => ['object', 'data'], 'variables' => ['object', 'data'], 'databases' => ['object', 'data']]]]);
    $response->assertJsonPath('attributes.relationships.allocation.attributes.id', $server->allocation_id);
    $response->assertJsonPath('attributes.relationships.user.attributes.id', $server->owner_id);
    $response->assertJsonPath('attributes.relationships.node.attributes.id', $server->node_id);
    $response->assertJsonPath('attributes.relationships.egg.attributes.id', $server->egg_id);
    $response->assertJsonPath('attributes.relationships.subusers.object', 'list');
    $response->assertJsonPath('attributes.relationships.databases.object', 'list');
    expect(count($response->json('attributes.relationships.subusers.data')))->toBeGreaterThanOrEqual(1);
    expect(count($response->json('attributes.relationships.databases.data')))->toBeGreaterThanOrEqual(1);
    // The BungeeCord egg ships variables, so the server variables relation is non-empty.
    expect(count($response->json('attributes.relationships.variables.data')))->toBeGreaterThanOrEqual(1);
    $ignored = $this->getJson(route('api.admin.servers.view', ['server' => $server->id, 'include' => 'does-not-exist']));
    $ignored->assertStatus(Response::HTTP_OK);
});
test('user includes', function () {
    $server = $this->createServerModel();
    $response = $this->getJson(route('api.admin.users.view', ['user' => $server->owner_id, 'include' => 'servers']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['attributes' => ['relationships' => ['servers' => ['object', 'data']]]]);
    $response->assertJsonPath('attributes.relationships.servers.object', 'list');
    expect(count($response->json('attributes.relationships.servers.data')))->toBeGreaterThanOrEqual(1);
    $response->assertJsonPath('attributes.relationships.servers.data.0.attributes.id', $server->id);
    $ignored = $this->getJson(route('api.admin.users.view', ['user' => $server->owner_id, 'include' => 'does-not-exist']));
    $ignored->assertStatus(Response::HTTP_OK);
});
test('node includes', function () {
    $server = $this->createServerModel();
    $response = $this->getJson(route('api.admin.nodes.view', ['node' => $server->node_id, 'include' => 'allocations,location,servers']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['attributes' => ['relationships' => ['allocations' => ['object', 'data'], 'location' => ['object', 'attributes'], 'servers' => ['object', 'data']]]]);
    $response->assertJsonPath('attributes.relationships.location.attributes.id', $server->location->id);
    expect(count($response->json('attributes.relationships.servers.data')))->toBeGreaterThanOrEqual(1);
    $ignored = $this->getJson(route('api.admin.nodes.view', ['node' => $server->node_id, 'include' => 'does-not-exist']));
    $ignored->assertStatus(Response::HTTP_OK);
});
test('egg includes', function () {
    $server = $this->createServerModel();
    $url = route('api.admin.eggs.view', ['egg' => $server->egg_id, 'include' => 'variables,servers']);
    $response = $this->getJson($url);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['attributes' => ['relationships' => ['variables' => ['object', 'data'], 'servers' => ['object', 'data']]]]);
    expect(count($response->json('attributes.relationships.variables.data')))->toBeGreaterThanOrEqual(1);
    expect(count($response->json('attributes.relationships.servers.data')))->toBeGreaterThanOrEqual(1);
    $ignored = $this->getJson(route('api.admin.eggs.view', ['egg' => $server->egg_id, 'include' => 'does-not-exist']));
    $ignored->assertStatus(Response::HTTP_OK);
});
test('location includes', function () {
    $server = $this->createServerModel();
    $location = $server->location;
    $response = $this->getJson(route('api.admin.locations.view', ['location' => $location->id, 'include' => 'nodes,servers']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['attributes' => ['relationships' => ['nodes' => ['object', 'data'], 'servers' => ['object', 'data']]]]);
    expect(count($response->json('attributes.relationships.nodes.data')))->toBeGreaterThanOrEqual(1);
    expect(count($response->json('attributes.relationships.servers.data')))->toBeGreaterThanOrEqual(1);
    $ignored = $this->getJson(route('api.admin.locations.view', ['location' => $location->id, 'include' => 'does-not-exist']));
    $ignored->assertStatus(Response::HTTP_OK);
});
test('allocation includes', function () {
    $server = $this->createServerModel();
    $response = $this->getJson(route('api.admin.nodes.allocations', ['node' => $server->node_id, 'include' => 'node,server', 'per_page' => 60]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['data' => [['attributes' => ['relationships' => ['node' => ['object', 'attributes'], 'server' => ['object']]]]]]);
    $assigned = collect($response->json('data'))->firstWhere('attributes.assigned', true);
    expect($assigned)->not->toBeNull('Expected the assigned allocation to be present in the collection.');
    expect($assigned['attributes']['relationships']['node']['attributes']['id'])->toBe($server->node_id);
    expect($assigned['attributes']['relationships']['server']['attributes']['id'])->toBe($server->id);
    $ignored = $this->getJson(route('api.admin.nodes.allocations', ['node' => $server->node_id, 'include' => 'does-not-exist']));
    $ignored->assertStatus(Response::HTTP_OK);
});
/** Build a server with a subuser and database so collection includes resolve to non-empty data. */
function createServerWithRelations(): Server
{
    return (function () {
        $server = $this->createServerModel();
        Subuser::query()->create(['user_id' => User::factory()->create()->id, 'server_id' => $server->id, 'permissions' => ['control.console']]);
        $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
        Database::factory()->create(['server_id' => $server->id, 'database_host_id' => $host->id]);

        return $server->refresh();
    })->call(pterodactylTestCase());
}

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

test('admin collections batch nested relationships and aggregates', function (string $resource, string $includes) {
    for ($i = 0; $i < 10; $i++) {
        $server = $this->createServerModel(['name' => 'nplusone-'.$i, 'egg_id' => \Pterodactyl\Models\Egg::factory()->create(['name' => 'nplusone-'.$i])->id]);
        $server->user->update(['username' => 'nplusone-'.$i]);
        $server->node->update(['name' => 'nplusone-'.$i]);
        $server->location->update(['short' => 'nplusone-'.$i]);
        \Pterodactyl\Models\EggVariable::factory()->create(['egg_id' => $server->egg_id]);
        Subuser::factory()->create(['server_id' => $server->id]);
        Database::factory()->create(['server_id' => $server->id, 'database_host_id' => DatabaseHost::factory()->create(['node_id' => $server->node_id, 'name' => 'nplusone-'.$i])->id]);
    }
    $filter = match ($resource) {
        'users' => 'username',
        'locations' => 'short',
        default => 'name',
    };
    $request = fn (int $limit) => $this->getJson('/api/admin/'.$resource.'?filter['.$filter.']=nplusone-&per_page='.$limit.($includes === '' ? '' : '&include='.$includes));
    $request(1)->assertOk();
    $one = measuredReads(fn () => $request(1));
    expect(measuredReads(fn () => $request(10)))->toBe($one, $resource);
    $request(10)->assertOk()->assertJsonCount(10, 'data');
})->with([
    ['servers', ''],
    ['servers', 'user,node,allocations,variables,databases,subusers'],
    ['nodes', 'servers.user,location'],
    ['users', 'servers.node'],
    ['locations', 'nodes,servers'],
    ['eggs', 'variables,tags,servers'],
    ['database-hosts', 'databases'],
]);
