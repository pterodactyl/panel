<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Eggs\EggImporterServiceTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Contracts\Eggs\ExportsEggs;
use Pterodactyl\Contracts\Eggs\ImportsEggs;
use Pterodactyl\Contracts\Eggs\UpdatesEggsFromImports;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class);
uses(DatabaseTransactions::class);
test('imports and updates eggs from typed share data', function () {
    $egg = $this->app->make(ImportsEggs::class)->import(fixture('minecraft/egg-paper.json'));
    expect($egg->name)->toBe('Paper');
    expect($egg->author)->toBe('parker@pterodactyl.io');
    expect($egg->docker_images['Java 21'])->toBe('ghcr.io/pterodactyl/yolks:java_21');
    expect($egg->variables()->count())->toBe(4);
    $updated = $this->app->make(UpdatesEggsFromImports::class)->update($egg, fixture('source-engine/egg-garrys-mod.json'));
    expect($updated->name)->toBe('Garrys Mod');
    expect($updated->docker_images)->toBe(['ghcr.io/pterodactyl/games:source' => 'ghcr.io/pterodactyl/games:source']);
    expect($updated->variables()->count())->toBe(8);
    expect($updated->variables()->where('env_variable', 'MINECRAFT_VERSION')->exists())->toBeFalse();
});
test('an import attaches its tags by slug, creating the missing ones', function (): void {
    $premium = Tag::factory()->create(['slug' => 'premium-tier']);
    $numeric = Tag::factory()->create();
    Tag::query()->where('slug', 'garrysmod')->delete();

    $egg = $this->app->make(ImportsEggs::class)->import(withTags(['premium-tier', 'garrysmod', 'Brand New', (string) $numeric->id]));

    expect($egg->tags()->orderBy('tags.slug')->get()->map(fn (Tag $tag): array => [$tag->slug, $tag->name])->all())->toBe([
        ['Brand New', 'Brand New'],
        ['garrysmod', "Garry's Mod"],
        [$premium->slug, $premium->name],
    ]);
});

test('an update replaces the tags only when the file has a tags key', function (): void {
    $egg = $this->app->make(ImportsEggs::class)->import(fixture('minecraft/egg-paper.json'));
    $kept = Tag::factory()->create();
    $egg->tags()->attach($kept);

    $updater = $this->app->make(UpdatesEggsFromImports::class);
    $updater->update($egg, fixture('minecraft/egg-paper.json'));
    expect($egg->tags()->pluck('tags.id')->all())->toBe([$kept->id]);

    $updater->update($egg, withTags(['replaced']));
    expect($egg->tags()->pluck('tags.slug')->all())->toBe(['replaced']);

    $updater->update($egg, withTags([]));
    expect($egg->tags()->exists())->toBeFalse();
});

test('an export carries the sorted tag slugs and imports back onto the same tags', function (): void {
    $egg = $this->app->make(ImportsEggs::class)->import(withTags(['zeta-tag', 'Alpha Tag']));

    $export = $this->app->make(ExportsEggs::class)->export($egg);
    expect(json_decode($export, true)['tags'])->toBe(['Alpha Tag', 'zeta-tag']);

    $copy = $this->app->make(ImportsEggs::class)->import(UploadedFile::fake()->createWithContent('egg.json', $export));
    expect($copy->tags()->orderBy('tags.id')->pluck('tags.id')->all())->toBe($egg->tags()->orderBy('tags.id')->pluck('tags.id')->all());
});

/** @param list<string> $tags */
function withTags(array $tags): UploadedFile
{
    $egg = json_decode((string) file_get_contents(base_path('database/Seeders/eggs/minecraft/egg-paper.json')), true);
    $egg['tags'] = $tags;

    return UploadedFile::fake()->createWithContent('egg.json', (string) json_encode($egg));
}

function fixture(string $path): UploadedFile
{
    $absolutePath = base_path('database/Seeders/eggs/'.$path);

    return new UploadedFile($absolutePath, basename($absolutePath), 'application/json', UPLOAD_ERR_OK, true);
}
