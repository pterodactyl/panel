<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Pterodactyl\Contracts\Mounts\DetachesEggsFromMounts;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Mount;

final readonly class DetachEggFromMount implements DetachesEggsFromMounts
{
    /**
     * Remove a single egg attachment from a mount.
     */
    public function detach(Mount $mount, Egg $egg): void
    {
        $mount->eggs()->detach($egg->id);
    }
}
