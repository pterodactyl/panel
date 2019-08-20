<?php

use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;
use App\Services\Nests\NestCreationService;
use App\Contracts\Repository\NestRepositoryInterface;

class NestSeeder extends Seeder
{
    /**
     * @var \App\Services\Nests\NestCreationService
     */
    private $creationService;

    /**
     * @var \App\Contracts\Repository\NestRepositoryInterface
     */
    private $repository;

    /**
     * NestSeeder constructor.
     *
     * @param \App\Services\Nests\NestCreationService           $creationService
     * @param \App\Contracts\Repository\NestRepositoryInterface $repository
     */
    public function __construct(
        NestCreationService $creationService,
        NestRepositoryInterface $repository
    ) {
        $this->creationService = $creationService;
        $this->repository = $repository;
    }

    /**
     * Run the seeder to add missing nests to the Panel.
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    public function run()
    {
        $items = $this->repository->findWhere([
            'author' => 'support@pterodactyl.io',
        ])->keyBy('name')->toArray();

        $this->createMinecraftNest(Arr::get($items, 'Minecraft'));
        $this->createSourceEngineNest(Arr::get($items, 'Source Engine'));
        $this->createVoiceServersNest(Arr::get($items, 'Voice Servers'));
        $this->createRustNest(Arr::get($items, 'Rust'));
    }

    /**
     * Create the Minecraft nest to be used later on.
     *
     * @param array|null $nest
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    private function createMinecraftNest(array $nest = null)
    {
        if (is_null($nest)) {
            $this->creationService->handle([
                'name' => 'Minecraft',
                'description' => 'Minecraft - the classic game from Mojang. With support for Vanilla MC, Spigot, and many others!',
            ], 'support@pterodactyl.io');
        }
    }

    /**
     * Create the Source Engine Games nest to be used later on.
     *
     * @param array|null $nest
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    private function createSourceEngineNest(array $nest = null)
    {
        if (is_null($nest)) {
            $this->creationService->handle([
                'name' => 'Source Engine',
                'description' => 'Includes support for most Source Dedicated Server games.',
            ], 'support@pterodactyl.io');
        }
    }

    /**
     * Create the Voice Servers nest to be used later on.
     *
     * @param array|null $nest
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    private function createVoiceServersNest(array $nest = null)
    {
        if (is_null($nest)) {
            $this->creationService->handle([
                'name' => 'Voice Servers',
                'description' => 'Voice servers such as Mumble and Teamspeak 3.',
            ], 'support@pterodactyl.io');
        }
    }

    /**
     * Create the Rust nest to be used later on.
     *
     * @param array|null $nest
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    private function createRustNest(array $nest = null)
    {
        if (is_null($nest)) {
            $this->creationService->handle([
                'name' => 'Rust',
                'description' => 'Rust - A game where you must fight to survive.',
            ], 'support@pterodactyl.io');
        }
    }
}
