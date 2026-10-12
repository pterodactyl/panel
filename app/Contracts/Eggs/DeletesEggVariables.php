<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\EggVariable;

interface DeletesEggVariables
{
    public function delete(EggVariable $variable): void;
}
