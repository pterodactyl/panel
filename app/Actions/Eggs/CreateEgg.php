<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Eggs\CreatesEggs;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Support\JsonValueGuard;

final readonly class CreateEgg implements CreatesEggs
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * Create an egg, stamping the configured service author.
     *
     * @param  EggCreationData  $data
     */
    public function create(array $data): Egg
    {
        $extensions = $data['extensions'] ?? [];
        unset($data['extensions']);

        return DB::transaction(function () use ($data, $extensions): Egg {
            $egg = Egg::query()->forceCreate([
                ...$data,
                'author' => JsonValueGuard::string(Config::get('pterodactyl.service.author')),
            ]);
            $this->extensions->save($egg, $extensions);

            return $egg->refresh();
        });
    }
}
