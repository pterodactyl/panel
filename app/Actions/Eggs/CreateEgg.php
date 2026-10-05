<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs;

use Illuminate\Support\Facades\Config;
use Pterodactyl\Contracts\Eggs\CreatesEggs;
use Pterodactyl\Models\Egg;
use Pterodactyl\Support\JsonValueGuard;

final readonly class CreateEgg implements CreatesEggs
{
    /**
     * Create an egg, stamping the configured service author.
     *
     * @param  EggCreationData  $data
     */
    public function create(array $data): Egg
    {
        $egg = Egg::query()->forceCreate([
            ...$data,
            'author' => JsonValueGuard::string(Config::get('pterodactyl.service.author')),
        ]);

        return $egg->refresh();
    }
}
