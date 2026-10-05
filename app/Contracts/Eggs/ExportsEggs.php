<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use JsonException;
use Pterodactyl\Models\Egg;

interface ExportsEggs
{
    /**
     * Return a JSON representation of an egg and its variables.
     *
     * @throws JsonException
     */
    public function export(Egg $egg): string;
}
