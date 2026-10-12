<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Tags\UpdateTagTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Tags\UpdatesTags;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('an update keeps the colour and legacy nest id it is not given', function (): void {
    $tag = Tag::factory()->create(['color' => '#123456', 'legacy_nest_id' => 3]);

    $this->app->make(UpdatesTags::class)->update($tag, ['name' => 'Renamed', 'slug' => 'renamed']);

    $tag->refresh();
    expect($tag->name)->toBe('Renamed')
        ->and($tag->slug)->toBe('renamed')
        ->and($tag->color)->toBe('#123456')
        ->and($tag->legacy_nest_id)->toBe(3);
});

test('a tag keeps its own slug, and a null colour clears it', function (): void {
    $tag = Tag::factory()->create(['slug' => 'mine', 'color' => '#123456']);

    $this->app->make(UpdatesTags::class)->update($tag, ['name' => 'Mine', 'slug' => 'mine', 'color' => null, 'legacy_nest_id' => '12']);

    $tag->refresh();
    expect($tag->slug)->toBe('mine')
        ->and($tag->color)->toBeNull()
        ->and($tag->legacy_nest_id)->toBe(12);
});

test('tags updated without the http api meet the same rules', function (array $input, string $field): void {
    Tag::factory()->create(['slug' => 'taken']);
    $tag = Tag::factory()->create()->refresh();
    $before = $tag->getAttributes();

    try {
        $this->app->make(UpdatesTags::class)->update($tag, [...['name' => $tag->name, 'slug' => $tag->slug], ...$input]);
        $this->fail('The tag was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($tag->fresh()->getAttributes())->toBe($before);
})->with([
    'a blank name' => [['name' => ''], 'name'],
    'a taken slug' => [['slug' => 'taken'], 'slug'],
    'a purely numeric slug' => [['slug' => '7'], 'slug'],
    'a colour that is not hex' => [['color' => '#12345g'], 'color'],
]);

test('built-in game tags cannot be edited, whatever the input', function (array $input): void {
    $tag = Tag::factory()->predefined('fivem')->create();

    expect(fn () => $this->app->make(UpdatesTags::class)->update($tag, $input))
        ->toThrow(DisplayException::class, 'Built-in game tags cannot be edited.');
    expect($tag->fresh()->slug)->toBe('fivem');
})->with([
    'valid input' => [['name' => 'Changed', 'slug' => 'changed']],
    'invalid input' => [[]],
]);
