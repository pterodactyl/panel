<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Variables;

use Pterodactyl\Contracts\Eggs\CreatesEggVariables;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;

final class CreateEggVariable implements CreatesEggVariables
{
    /**
     * Create a new variable for a given Egg from request-validated attributes.
     *
     * @param  EggVariableData  $data
     */
    public function create(Egg $egg, array $data): EggVariable
    {
        $variable = $egg->variables()->create($data);

        return $variable->refresh();
    }
}
