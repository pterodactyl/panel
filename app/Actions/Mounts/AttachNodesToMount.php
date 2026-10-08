<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Pterodactyl\Contracts\Mounts\AttachesNodesToMounts;
use Pterodactyl\Models\Mount;

final readonly class AttachNodesToMount implements AttachesNodesToMounts
{
    /**
     * Attach nodes to a mount, keeping any nodes already attached.
     *
     * @param  list<int>  $nodeIds
     */
    public function attach(Mount $mount, array $nodeIds): void
    {
        $mount->nodes()->syncWithoutDetaching($nodeIds);
    }
}
