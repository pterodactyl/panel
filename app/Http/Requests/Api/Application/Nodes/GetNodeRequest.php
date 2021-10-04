<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Models\Node;

class GetNodeRequest extends GetNodesRequest
{
    /**
     * Determine if the requested node exists on the Panel.
     */
    public function resourceExists(): bool
    {
        $node = $this->route()->parameter('node');

        return $node instanceof Node && $node->exists;
    }
}
