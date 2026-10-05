<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Servers\ServerControllerTest;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;

uses(ApplicationApiIntegrationTestCase::class);

test('automatic deployments accept integer location strings and validated boolean representations', function (string $surface, bool|int|string $dedicatedIp): void {
    $this->actingAs($this->getApiUser());
    $node = Node::factory()->for(Location::factory())->create();
    $allocation = Allocation::factory()->for($node)->create();
    $egg = Egg::query()->where('name', 'Bungeecord')->firstOrFail();
    $daemon = new FakeDaemonServer;
    $payload = deploymentPayload($surface, $this->getApiUser()->id, $egg->id, [
        'locations' => [(string) $node->location_id],
        'dedicated_ip' => $dedicatedIp,
        'port_range' => [],
    ]);

    $response = $this->postJson('/api/'.$surface.'/servers', $payload)->assertCreated();

    $this->assertDatabaseHas('servers', ['id' => $response->json('attributes.id'), 'node_id' => $node->id, 'allocation_id' => $allocation->id]);
    $daemon->assertCreated();
})->with(['admin', 'application'])->with([
    'false' => false,
    'true' => true,
    'zero' => 0,
    'one' => 1,
    'string zero' => '0',
    'string one' => '1',
]);

test('automatic deployments reject malformed constraints before creating a server', function (string $surface, string $field, mixed $value): void {
    $this->actingAs($this->getApiUser());
    $egg = Egg::query()->where('name', 'Bungeecord')->firstOrFail();
    $deploy = ['locations' => [], 'dedicated_ip' => false, 'port_range' => []];
    $deploy[$field] = $value;

    $this->postJson('/api/'.$surface.'/servers', deploymentPayload($surface, $this->getApiUser()->id, $egg->id, $deploy))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'deploy.'.$field);

    $this->assertDatabaseMissing('servers', ['name' => 'Deployed Server']);
})->with(['admin', 'application'])->with([
    'invalid boolean' => ['dedicated_ip', 'not-a-boolean'],
    'associative locations' => ['locations', ['first' => 1]],
    'associative port ranges' => ['port_range', ['first' => '25565']],
]);

test('automatic deployments reject malformed location identifiers with 422', function (string $surface, bool|float|string|null $value): void {
    $this->actingAs($this->getApiUser());
    $egg = Egg::query()->where('name', 'Bungeecord')->firstOrFail();

    $this->postJson('/api/'.$surface.'/servers', deploymentPayload($surface, $this->getApiUser()->id, $egg->id, ['locations' => [$value], 'dedicated_ip' => false, 'port_range' => []]), options: JSON_PRESERVE_ZERO_FRACTION)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'deploy.locations.0');

    $this->assertDatabaseMissing('servers', ['name' => 'Deployed Server']);
})->with(['admin', 'application'])->with(['boolean' => true, 'whole float' => 1.0, 'empty string' => '', 'null' => [null]]);

function deploymentPayload(string $surface, int $userId, int $eggId, array $deploy): array
{
    $limits = ['memory' => 512, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 0, 'threads' => null];
    $payload = [
        'name' => 'Deployed Server',
        'docker_image' => 'java:8',
        'startup' => 'java -jar server.jar',
        'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar'],
        'deploy' => $deploy,
    ];

    if ($surface === 'application') {
        return [...$payload, 'user' => $userId, 'egg' => $eggId, 'limits' => $limits, 'feature_limits' => ['databases' => 0, 'allocations' => 0, 'backups' => 0]];
    }

    return [...$payload, ...$limits, 'owner_id' => $userId, 'egg_id' => $eggId, 'database_limit' => 0, 'allocation_limit' => 0, 'backup_limit' => 0];
}

test('search filters servers', function (): void {
    $matching = $this->createServerModel(['name' => 'Audit Search Needle']);
    $this->createServerModel(['name' => 'Audit Search Haystack']);
    $this->getJson('/api/application/servers?search=Needle')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.id', $matching->id);
});
test('transfer relationship can be included', function (): void {
    $server = $this->createServerModel();
    $targetNode = Node::factory()->for(Location::factory())->create();
    $targetAllocation = Allocation::factory()->for($targetNode)->create();
    $transfer = ServerTransfer::factory()->create(['server_id' => $server->id, 'old_node' => $server->node_id, 'new_node' => $targetNode->id, 'old_allocation' => $server->allocation_id, 'new_allocation' => $targetAllocation->id]);
    $this->getJson('/api/application/servers/'.$server->id.'?include=transfer')->assertOk()->assertJsonPath('attributes.relationships.transfer.object', 'server_transfer')->assertJsonPath('attributes.relationships.transfer.attributes.id', $transfer->id);
});
test('missing transfer relationship is null', function (): void {
    $server = $this->createServerModel();
    $this->getJson('/api/application/servers/'.$server->id.'?include=transfer')->assertOk()->assertJsonPath('attributes.relationships.transfer.object', 'null_resource')->assertJsonPath('attributes.relationships.transfer.attributes', null);
});
test('retired nest include is ignored', function (): void {
    $egg = Egg::factory()->create();
    $server = $this->createServerModel(['egg_id' => $egg->id]);
    $this->getJson('/api/application/servers/'.$server->id.'?include=nest')->assertOk()->assertJsonMissingPath('attributes.nest')->assertJsonMissingPath('attributes.relationships.nest');
});
test('server list query count does not scale with results', function (): void {
    $this->createServerModel(['name' => 'First Server']);
    $this->getJson('/api/application/servers')->assertOk();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/api/application/servers')->assertOk();
    $oneServerQueryCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    $this->createServerModel(['name' => 'Second Server']);
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/api/application/servers')->assertOk();
    $twoServerQueryCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    expect($twoServerQueryCount)->toBe($oneServerQueryCount);
});
test('server list uses per server environment overrides', function (): void {
    $server = $this->createServerModel();
    $variable = $server->egg->variables()->firstOrFail();
    ServerVariable::query()->create(['server_id' => $server->id, 'variable_id' => $variable->id, 'variable_value' => 'application-api-override']);
    $this->getJson('/api/application/servers?filter[uuid]='.$server->uuid)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.container.environment.'.$variable->env_variable, 'application-api-override');
});

test('server includes are batched across the collection', function (): void {
    for ($i = 0; $i < 10; $i++) {
        $this->createServerModel();
    }

    $request = fn (int $limit) => $this->getJson('/api/application/servers?include=user,node.location,allocations&per_page='.$limit);
    $request(1)->assertOk();
    $counts = [];
    foreach ([1, 10] as $limit) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $request($limit)->assertOk()->assertJsonCount($limit, 'data');
            $counts[] = count(array_filter(DB::getQueryLog(), fn (array $query): bool => str_starts_with(mb_strtolower($query['query']), 'select')));
        } finally {
            DB::disableQueryLog();
        }
    }

    expect($counts[1])->toBe($counts[0]);
});

test('batch loading keeps unauthorized includes hidden and raw relationships out of node attributes', function (): void {
    $server = $this->createServerModel();
    $this->createNewDefaultApiKey($this->getApiUser(), ['r_users' => 0, 'r_server_databases' => 0]);
    $response = $this->getJson('/api/application/servers?include=user,databases,node.servers')->assertOk();
    $response->assertJsonPath('data.0.attributes.relationships.user.object', 'null_resource');
    $response->assertJsonPath('data.0.attributes.relationships.databases.object', 'null_resource');
    $response->assertJsonMissingPath('data.0.attributes.relationships.node.attributes.servers');
    $response->assertJsonPath('data.0.attributes.relationships.node.attributes.relationships.servers.data.0.attributes.id', $server->id);
});

test('variable parent include preserves egg metadata without querying each variable', function (): void {
    $server = $this->createServerModel();
    $variable = $server->egg->variables()->firstOrFail();
    ServerVariable::query()->create(['server_id' => $server->id, 'variable_id' => $variable->id, 'variable_value' => 'override']);
    $response = $this->getJson('/api/application/servers/'.$server->id.'?include=variables.parent')->assertOk();
    $row = collect($response->json('attributes.relationships.variables.data'))->firstWhere('attributes.id', $variable->id);
    expect($row['attributes']['server_value'])->toBe('override');
    expect($row['attributes']['relationships']['parent']['attributes']['env_variable'])->toBe($variable->env_variable);
});
