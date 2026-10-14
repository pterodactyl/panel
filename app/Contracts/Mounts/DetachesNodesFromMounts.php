<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;

interface DetachesNodesFromMounts
{
    public function detach(Mount $mount, Node $node): void;
}
