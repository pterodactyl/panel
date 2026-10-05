<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\Egg;

interface ImportsCatalogEggs
{
    public function import(string $id): Egg;
}
