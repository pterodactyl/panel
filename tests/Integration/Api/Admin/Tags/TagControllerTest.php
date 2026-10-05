<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Tags\TagControllerTest;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
test('tag crud', function () {
    $response = $this->postJson(route('api.admin.tags.store'), ['name' => 'Premium', 'slug' => 'premium', 'color' => '#123456']);
    $response->assertCreated()->assertJsonPath('attributes.slug', 'premium')->assertJsonPath('attributes.is_predefined', false);
    $tag = Tag::query()->where('slug', 'premium')->firstOrFail();
    $this->putJson(route('api.admin.tags.update', ['tag' => $tag->id]), ['name' => 'Premium Capacity', 'slug' => 'premium-capacity', 'color' => '#654321'])->assertOk()->assertJsonPath('attributes.slug', 'premium-capacity');
    $this->getJson(route('api.admin.tags', ['filter' => ['slug' => 'premium-capacity']]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.id', $tag->id);
    $this->delete(route('api.admin.tags.delete', ['tag' => $tag->id]))->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});
test('tag can be updated while keeping its own slug', function () {
    $tag = Tag::factory()->create(['name' => 'Premium', 'slug' => 'premium', 'color' => '#123456']);
    $this->putJson(route('api.admin.tags.update', ['tag' => $tag->id]), ['name' => 'Premium Capacity', 'slug' => 'premium', 'color' => '#654321'])->assertOk()->assertJsonPath('attributes.slug', 'premium')->assertJsonPath('attributes.name', 'Premium Capacity');
    $this->assertDatabaseHas('tags', ['id' => $tag->id, 'slug' => 'premium', 'name' => 'Premium Capacity']);
});
test('tag cannot take another tags slug', function () {
    Tag::factory()->create(['slug' => 'taken']);
    $tag = Tag::factory()->create(['slug' => 'mine']);
    $response = $this->putJson(route('api.admin.tags.update', ['tag' => $tag->id]), ['name' => 'Mine', 'slug' => 'taken']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'slug');
    $response->assertJsonPath('errors.0.meta.rule', 'unique');
    $this->assertDatabaseHas('tags', ['id' => $tag->id, 'slug' => 'mine']);
});
test('single tag can be viewed', function () {
    $tag = Tag::query()->create(['name' => 'Viewable', 'slug' => 'viewable', 'color' => '#abcdef']);
    $this->getJson(route('api.admin.tags.view', ['tag' => $tag->id]))->assertOk()->assertJsonPath('object', 'tag')->assertJsonPath('attributes.id', $tag->id)->assertJsonPath('attributes.slug', 'viewable');
    $this->getJson(route('api.admin.tags.view', ['tag' => 999999]))->assertNotFound();
});
test('built in tags cannot be updated', function () {
    /** @var Tag $tag */
    $tag = Tag::factory()->create(['name' => 'Minecraft', 'slug' => 'minecraft']);
    $this->putJson(route('api.admin.tags.update', ['tag' => $tag->id]), ['name' => 'Changed', 'slug' => 'changed'])->assertStatus(Response::HTTP_BAD_REQUEST)->assertJsonPath('errors.0.code', 'DisplayException');
});
test('built in tags cannot be deleted', function () {
    /** @var Tag $tag */
    $tag = Tag::factory()->create(['name' => 'Minecraft', 'slug' => 'minecraft']);
    $this->delete(route('api.admin.tags.delete', ['tag' => $tag->id]))->assertStatus(Response::HTTP_BAD_REQUEST)->assertJsonPath('errors.0.code', 'DisplayException');
});
test('tags sync independently to eggs and node kinds', function () {
    /** @var Tag $game */
    $game = Tag::factory()->create(['slug' => 'game-tag']);
    /** @var Tag $reservation */
    $reservation = Tag::factory()->create(['slug' => 'reservation-tag']);
    /** @var Egg $egg */
    $egg = Egg::factory()->create();
    /** @var Node $node */
    $node = Node::factory()->withLocation()->create();
    $this->putJson(route('api.admin.eggs.tags.sync', ['egg' => $egg->id]), ['tags' => [(string) $game->id]])->assertOk()->assertJsonPath('data.0.attributes.id', $game->id);
    $this->putJson(route('api.admin.nodes.tags.sync', ['node' => $node->id]), ['tags' => [(string) $game->id]])->assertOk();
    $this->putJson(route('api.admin.nodes.deployment-tags.sync', ['node' => $node->id]), ['tags' => [(string) $reservation->id]])->assertOk();
    expect($egg->tags()->pluck('tags.id')->all())->toBe([$game->id]);
    expect($node->eggTags()->pluck('tags.id')->all())->toBe([$game->id]);
    expect($node->deploymentTags()->pluck('tags.id')->all())->toBe([$reservation->id]);
});

test('syncing existing tags resolves ids and slugs in a single read', function () {
    $node = Node::factory()->withLocation()->create();
    $tags = Tag::factory()->count(10)->create();
    $values = $tags->map(fn (Tag $tag, int $index): string => $index % 2 === 0 ? (string) $tag->id : $tag->slug)->all();
    DB::flushQueryLog();
    DB::enableQueryLog();
    try {
        $this->putJson(route('api.admin.nodes.tags.sync', ['node' => $node->id]), ['tags' => [...$values, $values[0]]])->assertOk();
        $reads = array_filter(DB::getQueryLog(), fn (array $query): bool => str_starts_with(mb_strtolower($query['query']), 'select') && str_contains($query['query'], 'from `tags`') && ! str_contains($query['query'], 'join'));
        expect($reads)->toHaveCount(1);
    } finally {
        DB::disableQueryLog();
    }
    expect($node->eggTags()->pluck('tags.id')->sort()->values()->all())->toBe($tags->pluck('id')->sort()->values()->all());
});
