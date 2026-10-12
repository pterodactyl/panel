<?php

declare(strict_types=1);

namespace Database\Seeders;

use DirectoryIterator;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Eggs\ImportsEggs;
use Pterodactyl\Contracts\Eggs\UpdatesEggsFromImports;
use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Tag;

class EggSeeder extends Seeder
{
    /**
     * Directories under database/Seeders/eggs to import, in order, with the built-in tags
     * their eggs carry. These used to be nest names; they are now just the folders the
     * shipped egg files live in.
     *
     * @var array<string, list<EggSpecificTags>>
     */
    public static array $import = [
        'minecraft' => [EggSpecificTags::Minecraft],
        'source-engine' => [EggSpecificTags::Srcds],
        'voice-servers' => [EggSpecificTags::Voice],
        'rust' => [EggSpecificTags::Rust],
    ];

    /**
     * Built-in tags an egg file carries on top of its directory's.
     *
     * @var array<string, list<EggSpecificTags>>
     */
    public static array $fileTags = [
        'source-engine/egg-garrys-mod.json' => [EggSpecificTags::GarrysMod],
    ];

    public function __construct(protected ImportsEggs $importer, protected UpdatesEggsFromImports $updater) {}

    public function run(): void
    {
        foreach (static::$import as $directory => $tags) {
            $this->parseEggFiles($directory, $tags);
        }
    }

    /**
     * Eggs are matched on author and name alone now that they are no longer scoped
     * to a nest, so an egg only ever exists once across every seed directory.
     *
     * The seeder runs on every upgrade, so only a newly created egg gets the built-in
     * tags; an existing egg keeps exactly the tags an administrator left it with.
     *
     * @param  list<EggSpecificTags>  $tags
     */
    protected function parseEggFiles(string $directory, array $tags): void
    {
        $files = new DirectoryIterator(database_path('Seeders/eggs/'.$directory));

        $this->command->alert('Updating Eggs from: '.$directory);
        /** @var DirectoryIterator $file */
        foreach ($files as $file) {
            if (! $file->isFile() || ! $file->isReadable()) {
                continue;
            }

            $decoded = json_decode(file_get_contents($file->getRealPath()), true, 512, JSON_THROW_ON_ERROR);
            $seeded = [...$tags, ...(static::$fileTags[$directory.'/'.$file->getFilename()] ?? [])];
            $file = new UploadedFile($file->getPathname(), $file->getFilename(), 'application/json');

            DB::transaction(function () use ($decoded, $seeded, $file): void {
                $egg = Egg::query()
                    ->where('author', $decoded['author'])
                    ->where('name', $decoded['name'])
                    ->first();

                if ($egg instanceof Egg) {
                    $current = $egg->tags()->get();
                    $this->updater->update($egg, $file)->tags()->sync($current);
                    $this->command->info('Updated '.$decoded['name']);

                    return;
                }

                $this->importer->import($file)->tags()->syncWithoutDetaching(
                    Tag::findOrCreateFromSlugs(array_map(fn (EggSpecificTags $tag): string => $tag->value, $seeded))
                );
                $this->command->comment('Created '.$decoded['name']);
            });
        }

        $this->command->line('');
    }
}
