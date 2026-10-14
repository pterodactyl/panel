<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Servers\ServerTagCheckTest;

use Illuminate\Support\Str;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Tag;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonTransfer;

uses(AdminApiIntegrationTestCase::class);

function tag(string $slug): Tag
{
    return Tag::query()->firstOrCreate(['slug' => $slug], ['name' => Str::headline($slug)]);
}

function taggedEgg(Egg $egg, string $slug): Egg
{
    $egg->tags()->sync([tag($slug)->id]);

    return $egg;
}

function bungeecord(): Egg
{
    return Egg::query()->where('author', 'support@pterodactyl.io')->where('name', 'Bungeecord')->firstOrFail();
}

function taggedNode(?string $eggTag = null, ?string $deploymentTag = null): Node
{
    $node = Node::factory()->for(Location::factory())->create(['memory' => 102400, 'disk' => 102400]);
    if ($eggTag !== null) {
        $node->eggTags()->attach(tag($eggTag));
    }

    if ($deploymentTag !== null) {
        $node->deploymentTags()->attach(tag($deploymentTag));
    }

    return $node;
}

/** @return array<string, mixed> */
function serverPayload(Egg $egg, Allocation $allocation): array
{
    return [
        'name' => 'Tagged Server',
        'owner_id' => User::factory()->create()->id,
        'egg_id' => $egg->id,
        'docker_image' => 'ghcr.io/pterodactyl/yolks:java_21',
        'startup' => 'java -jar server.jar',
        'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar'],
        'memory' => 512,
        'swap' => 0,
        'disk' => 1024,
        'io' => 500,
        'cpu' => 0,
        'database_limit' => 0,
        'allocation_limit' => 0,
        'backup_limit' => 0,
        'primary_allocation_id' => $allocation->id,
    ];
}

/** @return array<string, array{string|null, string|null}> */
function acceptingNodes(): array
{
    return [
        'a node sharing a tag with the egg' => ['tag-check-game', null],
        'a node without egg tags' => [null, null],
        'a node with only a reservation' => [null, 'tag-check-reserved'],
    ];
}

test('server create refuses a node that does not accept the egg unless tags are ignored', function (): void {
    $egg = taggedEgg(bungeecord(), 'tag-check-game');
    $node = taggedNode('tag-check-other');
    $allocation = Allocation::factory()->for($node)->create();
    $fake = new FakeDaemonServer;

    $this->postJson(route('api.admin.servers.store'), serverPayload($egg, $allocation))
        ->assertUnprocessable()
        ->assertJsonCount(1, 'errors')
        ->assertJsonPath('errors.0.meta.source_field', 'primary_allocation_id')
        ->assertJsonPath('errors.0.detail', "{$node->name} only accepts eggs tagged Tag Check Other.");
    expect($fake->callsFor('create'))->toBe([]);

    $this->postJson(route('api.admin.servers.store'), [...serverPayload($egg, $allocation), 'ignore_tags' => true])->assertCreated();
    $fake->assertCreated();
});

test('server create accepts a node that accepts the egg', function (?string $eggTag, ?string $deploymentTag): void {
    $egg = taggedEgg(bungeecord(), 'tag-check-game');
    $allocation = Allocation::factory()->for(taggedNode($eggTag, $deploymentTag))->create();
    new FakeDaemonServer;

    $this->postJson(route('api.admin.servers.store'), serverPayload($egg, $allocation))->assertCreated();
})->with(acceptingNodes());

test('a transfer refuses a target node that does not accept the egg unless tags are ignored', function (): void {
    $server = $this->createServerModel();
    taggedEgg($server->egg, 'tag-check-game');
    $target = taggedNode('tag-check-other');
    $allocation = Allocation::factory()->for($target)->create(['server_id' => null]);
    $fake = new FakeDaemonTransfer;

    $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), ['node_id' => $target->id, 'allocation_id' => $allocation->id])
        ->assertUnprocessable()
        ->assertJsonCount(1, 'errors')
        ->assertJsonPath('errors.0.meta.source_field', 'node_id')
        ->assertJsonPath('errors.0.detail', "{$target->name} only accepts eggs tagged Tag Check Other.");
    $this->assertDatabaseMissing('server_transfers', ['server_id' => $server->id]);

    $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), ['node_id' => $target->id, 'allocation_id' => $allocation->id, 'ignore_tags' => true])->assertNoContent();
    $fake->assertNotified();
});

test('a transfer accepts a target node that accepts the egg', function (?string $eggTag, ?string $deploymentTag): void {
    $server = $this->createServerModel();
    taggedEgg($server->egg, 'tag-check-game');
    $target = taggedNode($eggTag, $deploymentTag);
    $allocation = Allocation::factory()->for($target)->create(['server_id' => null]);
    new FakeDaemonTransfer;

    $this->postJson(route('api.admin.servers.transfer', ['server' => $server->id]), ['node_id' => $target->id, 'allocation_id' => $allocation->id])->assertNoContent();
})->with(acceptingNodes());

test('an egg change refuses an egg the server\'s node does not accept unless tags are ignored', function (): void {
    $server = $this->createServerModel(['node_id' => taggedNode('tag-check-game')->id]);
    $egg = taggedEgg($this->cloneEggAndVariables(bungeecord()), 'tag-check-other');
    new FakeDaemonServer;
    $payload = ['startup' => 'java -jar server.jar', 'egg_id' => $egg->id, 'docker_image' => 'java:11', 'skip_scripts' => false, 'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar']];

    $this->putJson(route('api.admin.servers.startup', ['server' => $server->id]), $payload)
        ->assertUnprocessable()
        ->assertJsonCount(1, 'errors')
        ->assertJsonPath('errors.0.meta.source_field', 'egg_id')
        ->assertJsonPath('errors.0.detail', "{$server->node->name} only accepts eggs tagged Tag Check Game.");
    expect($server->refresh()->egg_id)->not->toBe($egg->id);

    $this->putJson(route('api.admin.servers.startup', ['server' => $server->id]), [...$payload, 'ignore_tags' => true])->assertOk();
    expect($server->refresh()->egg_id)->toBe($egg->id);
});

test('an egg change accepts an egg the server\'s node accepts', function (?string $eggTag, ?string $deploymentTag): void {
    $server = $this->createServerModel(['node_id' => taggedNode($eggTag, $deploymentTag)->id]);
    $egg = taggedEgg($this->cloneEggAndVariables(bungeecord()), 'tag-check-game');
    new FakeDaemonServer;

    $this->putJson(route('api.admin.servers.startup', ['server' => $server->id]), ['startup' => 'java -jar server.jar', 'egg_id' => $egg->id, 'docker_image' => 'java:11', 'skip_scripts' => false, 'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar']])
        ->assertOk();
    expect(Server::query()->findOrFail($server->id)->egg_id)->toBe($egg->id);
})->with(acceptingNodes());

test('keeping the egg is not checked against the node', function (): void {
    $server = $this->createServerModel(['node_id' => taggedNode('tag-check-other')->id]);
    taggedEgg($server->egg, 'tag-check-game');

    $this->putJson(route('api.admin.servers.startup', ['server' => $server->id]), ['startup' => 'java -jar server.jar', 'egg_id' => $server->egg_id, 'docker_image' => 'java:11', 'skip_scripts' => false, 'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar']])
        ->assertOk();
});
