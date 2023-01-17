<?php

namespace Database\Seeders;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Services\Eggs\Sharing\EggImporterService;
use Pterodactyl\Services\Eggs\Sharing\EggUpdateImporterService;

class EggSeeder extends Seeder
{
    /**
     * @var string[]
     */
    public static array $import = [
        'Minecraft',
        'Source Engine',
        'Voice Servers',
        'Rust',
    ];

    /**
     * EggSeeder constructor.
     */
    public function __construct(
        private EggImporterService $importerService,
        private EggUpdateImporterService $updateImporterService
    ) {
    }

    /**
     * Run the egg seeder.
     *
     * @throws \JsonException
     */
    public function run()
    {
        foreach (static::$import as $nest) {
            /* @noinspection PhpParamsInspection */
            $this->parseEggFiles(
                Nest::query()->where('author', 'support@pterodactyl.io')->where('name', $nest)->firstOrFail()
            );
        }
    }

    /**
     * Loop through the list of egg files and import them.
     *
     * @throws \JsonException
     */
    protected function parseEggFiles(Nest $nest)
    {
        $files = new \DirectoryIterator(database_path('Seeders/eggs/' . kebab_case($nest->name)));

        $this->command->alert('Updating Eggs for Nest: ' . $nest->name);
        /** @var \DirectoryIterator $file */
        foreach ($files as $file) {
            if (!$file->isFile() || !$file->isReadable()) {
                continue;
            }

            $decoded = json_decode(file_get_contents($file->getRealPath()), true, 512, JSON_THROW_ON_ERROR);
            $file = new UploadedFile($file->getPathname(), $file->getFilename(), 'application/json');

            $egg = $nest->eggs()
                ->where('author', $decoded['author'])
                ->where('name', $decoded['name'])
                ->first();

            if ($egg instanceof Egg) {
                $this->updateImporterService->handle($egg, $file);
                $this->command->info('Updated ' . $decoded['name']);
            } else {
                $this->importerService->handleFile($nest->id, $file);
                $this->command->comment('Created ' . $decoded['name']);
            }
        }

        $this->command->line('');
    }
}
