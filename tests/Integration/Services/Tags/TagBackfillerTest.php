<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Tags\TagBackfillerTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Tag;
use Pterodactyl\Services\Tags\TagBackfiller;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    // The seeded eggs already carry built-in tags; start without any so only the backfill's writes show.
    Tag::query()->delete();
});

/**
 * A legacy nest grouping the given number of new eggs.
 *
 * @return list<Egg>
 */
function nest(int $id, string $name, int $eggs): array
{
    DB::table('nests')->insert(['id' => $id, 'uuid' => (string) Str::uuid(), 'author' => 'nests@example.test', 'name' => $name]);

    return array_values(Egg::factory()->times($eggs)->create(['nest_id' => $id])->all());
}

/** @return list<string> */
function slugs(Egg $egg): array
{
    return $egg->tags()->pluck('tags.slug')->sort()->values()->all();
}

/** @return array{name: string, legacy_nest_id: int|null} */
function tagNamed(string $slug): array
{
    $tag = Tag::query()->where('slug', $slug)->sole();

    return ['name' => $tag->name, 'legacy_nest_id' => $tag->legacy_nest_id];
}

test('a nest that grouped several eggs becomes a tag named after it', function (): void {
    $custom = nest(9101, 'Custom Games', 2);
    $solo = nest(9102, 'Solo', 1);
    nest(9103, 'Empty', 0);
    $numeric = nest(9104, '123', 2);

    (new TagBackfiller(DB::connection()))->backfill();

    expect(slugs($custom[0]))->toBe(['Custom Games'])
        ->and(slugs($custom[1]))->toBe(['Custom Games'])
        ->and(tagNamed('Custom Games'))->toBe(['name' => 'Custom Games', 'legacy_nest_id' => 9101])
        ->and(slugs($solo[0]))->toBe([])
        ->and(slugs($numeric[0]))->toBe([])
        ->and(Tag::query()->count())->toBe(1);
});

test('a nest naming a built-in game folds onto its tag', function (): void {
    $minecraft = nest(9201, 'Minecraft', 1);
    $rust = nest(9202, ' RUST ', 2);
    $source = nest(9203, 'Source Engine', 2);
    $lower = nest(9204, 'custom games', 2);
    $upper = nest(9205, 'Custom Games', 2);

    (new TagBackfiller(DB::connection()))->backfill();

    expect(slugs($minecraft[0]))->toBe(['minecraft'])
        ->and(tagNamed('minecraft'))->toBe(['name' => 'Minecraft', 'legacy_nest_id' => null])
        ->and(slugs($rust[1]))->toBe(['rust'])
        ->and(tagNamed('rust'))->toBe(['name' => 'Rust', 'legacy_nest_id' => 9202])
        ->and(slugs($source[0]))->toBe(['srcds'])
        ->and(tagNamed('srcds'))->toBe(['name' => 'Source Engine', 'legacy_nest_id' => 9203])
        // Slugs that only differ in case are one tag, named by the first nest to claim it.
        ->and(slugs($lower[0]))->toBe(['custom games'])
        ->and(slugs($upper[0]))->toBe(['custom games'])
        ->and(tagNamed('custom games'))->toBe(['name' => 'custom games', 'legacy_nest_id' => 9204])
        ->and(Tag::query()->count())->toBe(4);
});

test('running the backfill again writes nothing new', function (): void {
    nest(9301, 'Custom Games', 2);
    $existing = Tag::factory()->create(['slug' => 'minecraft', 'name' => 'minecraft', 'legacy_nest_id' => null]);
    $minecraft = nest(9302, 'Minecraft', 2);
    $snapshot = fn (): array => [
        Tag::query()->orderBy('id')->get()->map(fn (Tag $tag): array => [$tag->id, $tag->slug, $tag->name, $tag->legacy_nest_id])->all(),
        DB::table('taggables')->orderBy('tag_id')->orderBy('taggable_id')->get()->map(fn (object $row): array => (array) $row)->all(),
    ];

    $first = (new TagBackfiller(DB::connection()))->backfill();
    $after = $snapshot();
    $second = (new TagBackfiller(DB::connection()))->backfill();

    expect($first)->toBe(4)
        ->and($second)->toBe(0)
        ->and($snapshot())->toBe($after)
        ->and($minecraft[0]->tags()->sole()->id)->toBe($existing->id);
});
