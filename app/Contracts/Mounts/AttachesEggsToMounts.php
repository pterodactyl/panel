<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;

interface AttachesEggsToMounts
{
    /**
     * Attach eggs to a mount, keeping any eggs already attached. The ids are validated here,
     * so they can come straight from any caller; errors are reported under `eggs` and `eggs.N`.
     *
     * @param  list<mixed>  $eggIds  ids of existing eggs, as integers or numeric strings
     */
    public function attach(Mount $mount, array $eggIds): void;
}
