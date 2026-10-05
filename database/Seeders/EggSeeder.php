<?php

declare(strict_types=1);

namespace Database\Seeders;

use DirectoryIterator;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Contracts\Eggs\ImportsEggs;
use Pterodactyl\Contracts\Eggs\UpdatesEggsFromImports;
use Pterodactyl\Models\Egg;

class EggSeeder extends Seeder
{
    /**
     * Directories under database/Seeders/eggs to import, in order. These used to be
     * nest names; they are now just the folders the shipped egg files live in.
     *
     * @var string[]
     */
    public static array $import = [
        'minecraft',
        'source-engine',
        'voice-servers',
        'rust',
    ];

    /**
     * EggSeeder constructor.
     */
    public function __construct(protected ImportsEggs $importer, protected UpdatesEggsFromImports $updater) {}

    /**
     * Run the egg seeder.
     */
    public function run(): void
    {
        foreach (static::$import as $directory) {
            $this->parseEggFiles($directory);
        }
    }

    /**
     * Loop through the list of egg files in one directory and import them.
     *
     * Eggs are matched on author and name alone now that they are no longer scoped
     * to a nest, so an egg only ever exists once across every seed directory.
     */
    protected function parseEggFiles(string $directory)
    {
        $files = new DirectoryIterator(database_path('Seeders/eggs/'.$directory));

        $this->command->alert('Updating Eggs from: '.$directory);
        /** @var DirectoryIterator $file */
        foreach ($files as $file) {
            if (! $file->isFile() || ! $file->isReadable()) {
                continue;
            }

            $decoded = json_decode(file_get_contents($file->getRealPath()), true, 512, JSON_THROW_ON_ERROR);
            $file = new UploadedFile($file->getPathname(), $file->getFilename(), 'application/json');

            $egg = Egg::query()
                ->where('author', $decoded['author'])
                ->where('name', $decoded['name'])
                ->first();

            if ($egg instanceof Egg) {
                $this->updater->update($egg, $file);
                $this->command->info('Updated '.$decoded['name']);
            } else {
                $this->importer->import($file);
                $this->command->comment('Created '.$decoded['name']);
            }
        }

        $this->command->line('');
    }
}
