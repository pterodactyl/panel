<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Mount;

interface DetachesEggsFromMounts
{
    public function detach(Mount $mount, Egg $egg): void;
}
