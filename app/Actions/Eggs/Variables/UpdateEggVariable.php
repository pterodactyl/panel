<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Variables;

use Pterodactyl\Contracts\Eggs\UpdatesEggVariables;
use Pterodactyl\Models\EggVariable;

final class UpdateEggVariable implements UpdatesEggVariables
{
    /**
     * Update an egg variable from request-validated attributes.
     *
     * @param  EggVariableData  $data
     */
    public function update(EggVariable $variable, array $data): EggVariable
    {
        $variable->update($data);

        return $variable;
    }
}
