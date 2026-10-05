<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;

interface CreatesEggVariables
{
    /**
     * Create a new variable for a given Egg from request-validated attributes.
     *
     * @param  EggVariableData  $data
     */
    public function create(Egg $egg, array $data): EggVariable;
}
