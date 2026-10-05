<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\Egg;

interface ReordersEggVariables
{
    /** @param list<int> $order */
    public function reorder(Egg $egg, array $order): void;
}
