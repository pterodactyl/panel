<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;

interface AttachesNodesToMounts
{
    /**
     * Attach nodes to a mount, keeping any nodes already attached.
     *
     * @param  list<int>  $nodeIds
     */
    public function attach(Mount $mount, array $nodeIds): void;
}
