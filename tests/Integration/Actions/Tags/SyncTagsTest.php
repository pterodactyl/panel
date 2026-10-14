<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Tags\SyncTagsTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Tags\SyncsTags;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('tag values synced without the http api meet the same rules', function (array $values, string $field): void {
    $egg = Egg::factory()->create();
    $count = Tag::query()->count();

    try {
        $this->app->make(SyncsTags::class)->syncEgg($egg, $values);
        $this->fail('The tags were synced.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($egg->tags()->exists())->toBeFalse()
        ->and(Tag::query()->count())->toBe($count);
})->with([
    'a name that is too long' => [[str_repeat('a', 192)], 'tags.0'],
    'a boolean' => [['fresh-name', true], 'tags.1'],
    'null' => [[null], 'tags.0'],
    'a float' => [[1.5], 'tags.0'],
    'a list' => [[['nested']], 'tags.0'],
]);

test('integer ids, numeric strings and names all resolve to tags', function (): void {
    $first = Tag::factory()->create();
    $second = Tag::factory()->create();
    $egg = Egg::factory()->create();

    // A numeric value that names no tag is a stale legacy nest id, and is dropped.
    $this->app->make(SyncsTags::class)->syncEgg($egg, [$first->id, (string) $second->id, ' Brand New ', '999999']);

    $created = Tag::query()->where('slug', 'Brand New')->sole();
    expect($egg->tags()->pluck('tags.id')->sort()->values()->all())->toBe([$first->id, $second->id, $created->id])
        ->and(Tag::query()->where('slug', '999999')->exists())->toBeFalse();
});

test('an empty list clears the tags', function (): void {
    $egg = Egg::factory()->create();
    $egg->tags()->attach(Tag::factory()->create());

    $this->app->make(SyncsTags::class)->syncEgg($egg, []);

    expect($egg->tags()->exists())->toBeFalse();
});

test('a node synced directly still cannot hold a tag as both kinds', function (): void {
    $tag = Tag::factory()->create(['name' => 'Both Kinds']);
    $node = Node::factory()->withLocation()->create();
    $sync = $this->app->make(SyncsTags::class);

    $sync->syncNodeGames($node, [$tag->id]);

    try {
        $sync->syncNodeDeployments($node, [$tag->id]);
        $this->fail('The tag was synced as both kinds.');
    } catch (ValidationException $validationException) {
        expect($validationException->errors())->toBe(['tags' => ['These tags are already on the node as the other kind: Both Kinds.']]);
    }

    expect($node->eggTags()->pluck('tags.id')->all())->toBe([$tag->id])
        ->and($node->deploymentTags()->exists())->toBeFalse();
});
