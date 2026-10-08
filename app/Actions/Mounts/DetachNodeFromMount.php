<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Pterodactyl\Contracts\Mounts\DetachesNodesFromMounts;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;

final readonly class DetachNodeFromMount implements DetachesNodesFromMounts
{
    /**
     * Remove a single node attachment from a mount.
     */
    public function detach(Mount $mount, Node $node): void
    {
        $mount->nodes()->detach($node->id);
    }
}
