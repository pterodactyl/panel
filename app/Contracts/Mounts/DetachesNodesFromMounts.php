<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;

interface DetachesNodesFromMounts
{
    /**
     * Remove a single node attachment from a mount.
     */
    public function detach(Mount $mount, Node $node): void;
}
