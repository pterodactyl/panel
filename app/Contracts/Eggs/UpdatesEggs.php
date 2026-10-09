<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\Egg;

interface UpdatesEggs
{
    /**
     * Update an egg from request-validated attributes.
     *
     * @param  EggCreationData  $data
     */
    public function update(Egg $egg, array $data): Egg;
}
