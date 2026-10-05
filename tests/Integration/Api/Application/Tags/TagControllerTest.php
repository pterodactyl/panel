<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Tags\TagControllerTest;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

uses(ApplicationApiIntegrationTestCase::class);
test('list all tags', function (): void {
    $customTags = Tag::factory()->count(2)->create(['legacy_nest_id' => null]);
    $tags = Tag::query()->get();
    $response = $this->getJson('/api/application/tags')->assertOk();
    $response->assertJsonCount($tags->count(), 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'name', 'slug', 'color', 'is_predefined', 'legacy_nest_id', 'created_at', 'updated_at']]], 'meta' => ['pagination']]);
    foreach ($customTags as $tag) {
        $attributes = collect($response->json('data'))->firstWhere('attributes.id', $tag->id)['attributes'];
        expect($attributes)->toBe([
            'id' => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'color' => $tag->color,
            'is_predefined' => false,
            'legacy_nest_id' => null,
            'relationships' => [],
            'created_at' => $tag->created_at->toAtomString(),
            'updated_at' => $tag->updated_at->toAtomString(),
        ]);
    }
});
test('filter tags by slug', function (): void {
    $tag = Tag::factory()->create(['slug' => 'filter-me']);
    Tag::factory()->create();
    $response = $this->getJson('/api/application/tags?filter[slug]=filter-me')->assertOk();
    expect($response->json('data.*.attributes.id'))->toBe([$tag->id]);
});
test('return single tag', function (): void {
    $tag = Tag::factory()->create(['legacy_nest_id' => 42]);
    $this->getJson('/api/application/tags/'.$tag->id)->assertOk()->assertJson(['object' => 'tag', 'attributes' => ['id' => $tag->id, 'name' => $tag->name, 'slug' => $tag->slug, 'color' => $tag->color, 'is_predefined' => false, 'legacy_nest_id' => 42, 'relationships' => [], 'created_at' => $tag->created_at->toAtomString(), 'updated_at' => $tag->updated_at->toAtomString()]], true);
});
test('built in tag reports its built in name and color', function (): void {
    $tag = Tag::factory()->predefined('minecraft')->create(['name' => 'Something Else']);
    $this->getJson('/api/application/tags/'.$tag->id)->assertOk()
        ->assertJsonPath('attributes.name', 'Minecraft')
        ->assertJsonPath('attributes.is_predefined', true)
        ->assertJsonPath('attributes.color', '#5B8731');
});
test('return single tag with relationships', function (): void {
    $tag = Tag::factory()->create();
    $egg = Egg::factory()->create();
    $egg->tags()->attach($tag);
    $node = Node::factory()->withLocation()->create();
    $node->eggTags()->attach($tag);
    $response = $this->getJson('/api/application/tags/'.$tag->id.'?include=eggs,nodes')->assertOk();
    expect($response->json('attributes.relationships.eggs.data.*.attributes.id'))->toBe([$egg->id]);
    expect($response->json('attributes.relationships.nodes.data.*.attributes.id'))->toBe([$node->id]);
});
test('key without node permission cannot load nodes', function (): void {
    $this->createNewDefaultApiKey($this->getApiUser(), ['r_nodes' => 0]);
    $tag = Tag::factory()->create();
    Node::factory()->withLocation()->create()->eggTags()->attach($tag);
    $this->getJson('/api/application/tags/'.$tag->id.'?include=nodes')->assertOk()
        ->assertJson(['attributes' => ['relationships' => ['nodes' => ['object' => 'null_resource', 'attributes' => null]]]]);
});
test('missing tag returns not found', function (): void {
    $this->assertNotFoundJson($this->getJson('/api/application/tags/nil'));
});
test('egg read permission is required', function (string $url): void {
    $tag = Tag::factory()->create();
    $this->createNewDefaultApiKey($this->getApiUser(), ['r_eggs' => 0]);
    $this->assertAccessDeniedJson($this->getJson(str_replace('{tag}', (string) $tag->id, $url)));
})->with(['/api/application/tags', '/api/application/tags/{tag}']);
