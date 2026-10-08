<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Mount;

interface DetachesEggsFromMounts
{
    /**
     * Remove a single egg attachment from a mount.
     */
    public function detach(Mount $mount, Egg $egg): void;
}
