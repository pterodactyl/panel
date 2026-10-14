<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Tags\CreateTagTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Tags\CreatesTags;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, int|string|null> */
function tag(array $overrides = []): array
{
    return [...['name' => 'Direct', 'slug' => 'direct-tag'], ...$overrides];
}

test('tags created without the http api meet the same rules', function (array $input, string $field): void {
    Tag::factory()->create(['slug' => 'taken']);
    $count = Tag::query()->count();

    try {
        $this->app->make(CreatesTags::class)->create(tag($input));
        $this->fail('The tag was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect(Tag::query()->count())->toBe($count);
})->with([
    'a missing name' => [['name' => null], 'name'],
    'a name that is too long' => [['name' => str_repeat('a', 192)], 'name'],
    'a missing slug' => [['slug' => null], 'slug'],
    'a taken slug' => [['slug' => 'taken'], 'slug'],
    'a purely numeric slug' => [['slug' => '42'], 'slug'],
    'a colour that is not hex' => [['color' => 'red'], 'color'],
    'a legacy nest id that is not a number' => [['legacy_nest_id' => 'one'], 'legacy_nest_id'],
]);

test('values are coerced the way laravel validates them', function (int|string $legacyNestId): void {
    $tag = $this->app->make(CreatesTags::class)->create(tag(['color' => '#a1b2c3', 'legacy_nest_id' => $legacyNestId]));

    $tag->refresh();
    expect($tag->name)->toBe('Direct')
        ->and($tag->slug)->toBe('direct-tag')
        ->and($tag->color)->toBe('#a1b2c3')
        ->and($tag->legacy_nest_id)->toBe(7);
})->with([[7], ['7']]);

test('a tag given no colour or a blank one has none', function (?string $color): void {
    $tag = $this->app->make(CreatesTags::class)->create(tag($color === null ? [] : ['color' => $color]));

    expect($tag->refresh()->color)->toBeNull()
        ->and($tag->legacy_nest_id)->toBeNull();
})->with([[null], ['']]);
