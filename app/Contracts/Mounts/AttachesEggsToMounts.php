<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;

interface AttachesEggsToMounts
{
    /**
     * Attach eggs to a mount, keeping any eggs already attached.
     *
     * @param  list<int>  $eggIds
     */
    public function attach(Mount $mount, array $eggIds): void;
}
