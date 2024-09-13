<?php

namespace Database\Seeders;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Services\Eggs\Sharing\EggImporterService;
use Pterodactyl\Services\Eggs\Sharing\EggUpdateImporterService;

class UpdatedEggSeeder extends Seeder
{
    private const EGG_PATH = '/srv/pterodactyl/seeders/eggs/';
    // private const EGG_PATH = 'seeders/eggs/';
    private const AUTHOR_EMAIL = 'support@pterodactyl.io';

    protected EggImporterService $importerService;
    protected EggUpdateImporterService $updateImporterService;

    public function __construct(
        EggImporterService $importerService,
        EggUpdateImporterService $updateImporterService
    ) {
        $this->importerService = $importerService;
        $this->updateImporterService = $updateImporterService;
    }

    public function run()
    {
        $this->command->info("Looking for nests with author: " . self::AUTHOR_EMAIL);
    
        $nests = Nest::where('author', self::AUTHOR_EMAIL)->get();

        $this->command->info("Found " . $nests->count() . " nests");

        // Print out all nests in the database
        $allNests = Nest::all();
        $this->command->info("Total nests in database: " . $allNests->count());
        
        foreach ($allNests as $nest) {
            $this->command->info("Nest: " . $nest->name . ", Author: " . $nest->author);
        }

        if ($nests->isEmpty()) {
            $this->command->error('No nests found. Please run NestSeeder first.');
            return;
        }

        foreach ($nests as $nest) {
            $this->parseEggFiles($nest);
        }
    }

    protected function parseEggFiles(Nest $nest)
    {
        $eggPath = self::EGG_PATH . kebab_case($nest->name);
        // $eggPath = storage_path('app/' . self::EGG_PATH . kebab_case($nest->name));
        
        if (!is_dir($eggPath)) {
            $this->command->warn("No egg directory found for nest: {$nest->name}");
            return;
        }

        $files = new \DirectoryIterator($eggPath);

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
                $this->importerService->handle($file, $nest->id);
                $this->command->comment('Created ' . $decoded['name']);
            }
        }

        $this->command->line('');
    }
}