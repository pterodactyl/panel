<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Variables;

use Pterodactyl\Contracts\Eggs\UpdatesEggVariables;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;

final class UpdateEggVariable implements UpdatesEggVariables
{
    /**
     * Update an egg variable with the fields it is created with.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(EggVariable $variable, array $input): EggVariable
    {
        $egg = Egg::query()->findOrFail($variable->egg_id);

        $variable->update(CreateEggVariable::attributes($input, $egg, $variable));

        return $variable;
    }
}
