<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Pterodactyl\Contracts\Mounts\AttachesEggsToMounts;
use Pterodactyl\Models\Mount;

final readonly class AttachEggsToMount implements AttachesEggsToMounts
{
    /**
     * Attach eggs to a mount, keeping any eggs already attached.
     *
     * @param  list<int>  $eggIds
     */
    public function attach(Mount $mount, array $eggIds): void
    {
        $mount->eggs()->syncWithoutDetaching($eggIds);
    }
}
