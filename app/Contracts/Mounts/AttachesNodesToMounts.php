<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;

interface AttachesNodesToMounts
{
    /**
     * Attach nodes to a mount, keeping any nodes already attached. The ids are validated here,
     * so they can come straight from any caller; errors are reported under `nodes` and `nodes.N`.
     *
     * @param  list<mixed>  $nodeIds  ids of existing nodes, as integers or numeric strings
     */
    public function attach(Mount $mount, array $nodeIds): void;
}
