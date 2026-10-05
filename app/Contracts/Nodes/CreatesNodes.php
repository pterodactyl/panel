<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Nodes;

use Pterodactyl\Models\Node;

interface CreatesNodes
{
    /**
     * Create a node and mint its daemon token pair.
     *
     * @param  NodeCreationData  $data
     */
    public function create(array $data): Node;
}
