<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Variables;

use Pterodactyl\Contracts\Eggs\DeletesEggVariables;
use Pterodactyl\Models\EggVariable;

final readonly class DeleteEggVariable implements DeletesEggVariables
{
    /**
     * Delete an egg variable.
     */
    public function delete(EggVariable $variable): void
    {
        $variable->delete();
    }
}
