<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Database\EggSeederTest;

use Database\Seeders\EggSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Eggs\UpdatesEggsFromImports;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return list<string> */
function slugs(string $egg): array
{
    return Egg::query()->where('name', $egg)->sole()->tags()->pluck('tags.slug')->sort()->values()->all();
}

test('seeded eggs carry the built-in tags of their directory', function (): void {
    DB::table('taggables')->delete();
    Tag::query()->whereIn('slug', ['minecraft', 'srcds', 'garrysmod', 'voice', 'rust'])->delete();

    $this->seed(EggSeeder::class);

    expect(slugs('Paper'))->toBe(['minecraft'])
        ->and(slugs('Garrys Mod'))->toBe(['garrysmod', 'srcds'])
        ->and(slugs('Insurgency'))->toBe(['srcds'])
        ->and(slugs('Teamspeak3 Server'))->toBe(['voice'])
        ->and(slugs('Rust'))->toBe(['rust'])
        ->and(Tag::query()->where('slug', 'srcds')->sole()->name)->toBe('Source Engine');
});

test('reseeding never removes a tag, even when an egg file replaces them', function (): void {
    $custom = Tag::factory()->create(['slug' => 'admin-added']);
    Egg::query()->where('name', 'Paper')->sole()->tags()->attach($custom);

    // An egg file carrying tags replaces the egg's tags on update.
    $this->app->extend(UpdatesEggsFromImports::class, fn (UpdatesEggsFromImports $updater): UpdatesEggsFromImports => new readonly class($updater) implements UpdatesEggsFromImports
    {
        public function __construct(private UpdatesEggsFromImports $updater) {}

        public function update(Egg $egg, UploadedFile $file): Egg
        {
            $egg = $this->updater->update($egg, $file);
            $egg->tags()->sync(Tag::findOrCreateFromSlugs(['from-file']));

            return $egg;
        }
    });

    $this->seed(EggSeeder::class);
    $this->seed(EggSeeder::class);

    expect(slugs('Paper'))->toBe(['admin-added', 'from-file', 'minecraft'])
        ->and(slugs('Rust'))->toBe(['from-file', 'rust']);
});
