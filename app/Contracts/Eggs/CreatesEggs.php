<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\Egg;

interface CreatesEggs
{
    /**
     * Create an egg, stamping the configured service author.
     *
     * @param  EggCreationData  $data
     */
    public function create(array $data): Egg;
}
