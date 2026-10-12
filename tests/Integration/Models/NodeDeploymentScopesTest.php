<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Models\NodeDeploymentScopesTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
beforeEach(function () {
    Database::query()->delete();
    Server::query()->delete();
    Node::query()->delete();
});
test('viable nodes have room for the server in the given locations', function () {
    /** @var Location[] $locations */
    $locations = Location::factory()->times(2)->create();
    /** @var Node[] $nodes */
    $nodes = [
        // Only returned while no location is given.
        Node::factory()->create(['location_id' => $locations[0]->id, 'memory' => 2048, 'disk' => 1024 * 100]),
        Node::factory()->create(['location_id' => $locations[1]->id, 'memory' => 1024, 'disk' => 10240, 'disk_overallocate' => 10]),
        Node::factory()->create(['location_id' => $locations[1]->id, 'memory' => 1024 * 4, 'memory_overallocate' => 50, 'disk' => 102400]),
    ];
    $viable = fn (int $memory, int $disk, array $locations = []): Collection => Node::query()->viableFor($memory, $disk)->inLocations($locations)->get();

    expect($viable(512, 512))->toHaveCount(3);
    // Only the last node has enough memory.
    $response = $viable(2049, 512);
    expect($response)->toHaveCount(1);
    expect($response[0]->id)->toBe($nodes[2]->id);
    // Both nodes in the location are under their limits.
    $response = $viable(512, 512, [$locations[1]->id]);
    expect($response)->toHaveCount(2);
    expect($response->where('location_id', $locations[1]->id)->count())->toBe(2);
    // Over the memory of the first node in the location.
    $response = $viable(2048, 512, [$locations[1]->id]);
    expect($response)->toHaveCount(1);
    expect($response[0]->id)->toBe($nodes[2]->id);
    // Over the disk of the first node in the location, even with its overallocation.
    $response = $viable(256, 20480, [$locations[1]->id]);
    expect($response)->toHaveCount(1);
    expect($response[0]->id)->toBe($nodes[2]->id);
    // Exactly at the disk limit of the first node once its overallocation is counted.
    expect($viable(256, 11264, [$locations[1]->id]))->toHaveCount(2);
    // Servers on the first node use up its base disk, leaving no room for 1GB more.
    $servers = Collection::make([$this->createServerModel(['node_id' => $nodes[1]->id, 'disk' => 5120]), $this->createServerModel(['node_id' => $nodes[1]->id, 'disk' => 5120])]);
    $response = $viable(256, 1024, [$locations[1]->id]);
    expect($response)->toHaveCount(1);
    expect($response[0]->id)->toBe($nodes[2]->id);
    $servers->each->delete();
    // More memory than either node has, even with overallocation.
    expect($viable(10000, 512, [$locations[1]->id]))->toBeEmpty();
});
test('non public nodes are never viable', function () {
    Node::factory()->withLocation()->create(['public' => false, 'memory' => 2048, 'disk' => 2048]);

    expect(Node::query()->viableFor(0, 0)->get())->toBeEmpty();
});
test('untagged node accepts any egg', function () {
    expect(accepts([], [], ['rust']))->toBeTrue();
    expect(accepts([], [], []))->toBeTrue();
});
test('game match is or', function () {
    $node = ['rust', 'minecraft'];
    expect(accepts($node, [], ['rust']))->toBeTrue();
    expect(accepts($node, [], ['minecraft']))->toBeTrue();
    expect(accepts($node, [], ['minecraft', 'bedrock']))->toBeTrue();
    expect(accepts($node, [], ['garrysmod']))->toBeFalse();
});
test('untagged egg only lands on untagged node', function () {
    expect(accepts(['rust'], [], []))->toBeFalse();
    expect(accepts([], [], []))->toBeTrue();
});
test('reservation blocks unopted deploy', function () {
    expect(accepts(['rust'], ['premium'], ['rust'], []))->toBeFalse();
    expect(accepts(['rust'], ['premium'], ['rust'], ['premium']))->toBeTrue();
});
test('targeted deploy requires every tag on the node', function () {
    expect(accepts(['rust'], [], ['rust'], ['premium']))->toBeFalse();
    expect(accepts(['rust'], ['premium'], ['rust'], ['premium', 'eu']))->toBeFalse();
    expect(accepts(['rust'], ['premium', 'eu'], ['rust'], ['premium', 'eu']))->toBeTrue();
});
test('node with no games but a reservation', function () {
    expect(accepts([], ['premium'], ['rust'], ['premium']))->toBeTrue();
    expect(accepts([], ['premium'], ['minecraft'], ['premium']))->toBeTrue();
    // ...but an untargeted deploy still cannot claim a reserved node.
    expect(accepts([], ['premium'], ['rust'], []))->toBeFalse();
});
test('reservation does not leak through the game or', function () {
    expect(accepts(['rust', 'minecraft'], ['premium'], ['minecraft'], ['budget']))->toBeFalse();
    expect(accepts(['rust', 'minecraft'], ['premium'], ['minecraft'], ['premium']))->toBeTrue();
});
/**
 * @param  list<string>  $nodeEggTags
 * @param  list<string>  $nodeDeploymentTags
 * @param  list<string>  $eggTags
 * @param  list<string>  $deployTags
 */
function accepts(array $nodeEggTags, array $nodeDeploymentTags, array $eggTags, array $deployTags = []): bool
{
    $tag = fn (string $slug): int => Tag::query()->firstOrCreate(['slug' => $slug], ['name' => $slug])->id;

    $node = Node::factory()->withLocation()->create();
    $node->eggTags()->attach(array_map($tag, $nodeEggTags));
    $node->deploymentTags()->attach(array_map($tag, $nodeDeploymentTags));

    return Node::query()->whereKey($node->id)->forEggTags($eggTags)->forDeployTags($deployTags)->exists();
}
