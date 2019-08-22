<?php

use App\Models\Nest;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use App\Services\Eggs\Sharing\EggImporterService;
use App\Contracts\Repository\EggRepositoryInterface;
use App\Contracts\Repository\NestRepositoryInterface;
use App\Exceptions\Repository\RecordNotFoundException;
use App\Services\Eggs\Sharing\EggUpdateImporterService;

class EggSeeder extends Seeder
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var \App\Services\Eggs\Sharing\EggImporterService
     */
    private $importerService;

    /**
     * @var \App\Contracts\Repository\NestRepositoryInterface
     */
    private $nestRepository;

    /**
     * @var \App\Contracts\Repository\EggRepositoryInterface
     */
    private $repository;

    /**
     * @var \App\Services\Eggs\Sharing\EggUpdateImporterService
     */
    private $updateImporterService;

    /**
     * EggSeeder constructor.
     *
     * @param \App\Services\Eggs\Sharing\EggImporterService       $importerService
     * @param \App\Contracts\Repository\EggRepositoryInterface    $repository
     * @param \App\Services\Eggs\Sharing\EggUpdateImporterService $updateImporterService
     * @param \Illuminate\Filesystem\Filesystem                           $filesystem
     * @param \App\Contracts\Repository\NestRepositoryInterface   $nestRepository
     */
    public function __construct(
        EggImporterService $importerService,
        EggRepositoryInterface $repository,
        EggUpdateImporterService $updateImporterService,
        Filesystem $filesystem,
        NestRepositoryInterface $nestRepository
    ) {
        $this->filesystem = $filesystem;
        $this->importerService = $importerService;
        $this->repository = $repository;
        $this->updateImporterService = $updateImporterService;
        $this->nestRepository = $nestRepository;
    }

    /**
     * Run the egg seeder.
     */
    public function run()
    {
        $this->getEggsToImport()->each(function ($nest) {
            $this->parseEggFiles($this->findMatchingNest($nest));
        });
    }

    /**
     * Return a list of eggs to import.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getEggsToImport(): Collection
    {
        return collect([
            'Minecraft',
            'Source Engine',
            'Voice Servers',
            'Rust',
        ]);
    }

    /**
     * Find the nest that these eggs should be attached to.
     *
     * @param string $nestName
     * @return \App\Models\Nest
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    private function findMatchingNest(string $nestName): Nest
    {
        return $this->nestRepository->findFirstWhere([
            ['author', '=', 'support@pterodactyl.io'],
            ['name', '=', $nestName],
        ]);
    }

    /**
     * Loop through the list of egg files and import them.
     *
     * @param \App\Models\Nest $nest
     */
    private function parseEggFiles(Nest $nest)
    {
        $files = $this->filesystem->allFiles(database_path('seeds/eggs/' . Str::kebab($nest->name)));

        $this->command->alert('Updating Eggs for Nest: ' . $nest->name);
        collect($files)->each(function ($file) use ($nest) {
            /* @var \Symfony\Component\Finder\SplFileInfo $file */
            $decoded = json_decode($file->getContents());
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->command->error('JSON decode exception for ' . $file->getFilename() . ': ' . json_last_error_msg());
            }

            $file = new UploadedFile($file->getPathname(), $file->getFilename(), 'application/json', $file->getSize());

            try {
                $egg = $this->repository->setColumns('id')->findFirstWhere([
                    ['author', '=', $decoded->author],
                    ['name', '=', $decoded->name],
                    ['nest_id', '=', $nest->id],
                ]);

                $this->updateImporterService->handle($egg->id, $file);

                return $this->command->info('Updated ' . $decoded->name);
            } catch (RecordNotFoundException $exception) {
                $this->importerService->handle($file, $nest->id);

                return $this->command->comment('Created ' . $decoded->name);
            }
        });

        $this->command->line('');
    }
}
