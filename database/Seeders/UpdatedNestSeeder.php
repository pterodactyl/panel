<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Pterodactyl\Services\Nests\NestCreationService;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;

class UpdatedNestSeeder extends Seeder
{
    private const JSON_FILE_PATH = '/srv/pterodactyl/seeders/nests.json';

    private const AUTHOR_EMAIL = 'support@pterodactyl.io';
    // private const JSON_FILE_PATH = 'seeders/nests.json';

    private $creationService;
    private $repository;

    public function __construct(
        NestCreationService $creationService,
        NestRepositoryInterface $repository
    ) {
        $this->creationService = $creationService;
        $this->repository = $repository;
    }

    public function run()
    {
        if (!file_exists(self::JSON_FILE_PATH)) {
            $this->command->error('nests.json file not found in the specified directory.');
            return;
        }

        $json = file_get_contents(self::JSON_FILE_PATH);

        // if (!Storage::disk('local')->exists(self::JSON_FILE_PATH)) {
        //     $this->command->error('nests.json file not found in storage/app/seeders directory.');
        //     return;
        // }

        // $json = Storage::disk('local')->get(self::JSON_FILE_PATH);
        $nestsData = json_decode($json, true);

        if (!isset($nestsData['nests']) || !is_array($nestsData['nests'])) {
            $this->command->error('Invalid or empty nests.json file.');
            return;
        }

        $existingNests = $this->repository->findWhere([
            'author' => self::AUTHOR_EMAIL,
        ])->keyBy('name')->toArray();

        foreach ($nestsData['nests'] as $nestData) {
            $this->createNest($nestData, $existingNests);
        }
    }

    private function createNest(array $nestData, array $existingNests)
    {
        if (!isset($nestData['name']) || !isset($nestData['description'])) {
            $this->command->warn("Skipping invalid nest data: " . json_encode($nestData));
            return;
        }

        if (!array_key_exists($nestData['name'], $existingNests)) {
            $createdNest = $this->creationService->handle([
                'name' => $nestData['name'],
                'description' => $nestData['description'],
            ], self::AUTHOR_EMAIL);
            $this->command->info("Created nest: {$nestData['name']} with ID: {$createdNest->id}");
        } else {
            $this->command->info("Nest already exists: {$nestData['name']}");
        }
    }
}