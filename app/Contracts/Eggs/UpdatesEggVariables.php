<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\EggVariable;

interface UpdatesEggVariables
{
    /**
     * Update an egg variable from request-validated attributes.
     *
     * @param  EggVariableData  $data
     */
    public function update(EggVariable $variable, array $data): void;
}
