<?php

namespace Pterodactyl\Http\Requests\API\Admin\Nodes;

use Pterodactyl\Models\Node;
use Pterodactyl\Http\Requests\API\Admin\ApiAdminRequest;

class GetNodeRequest extends ApiAdminRequest
{
    /**
     * Determine if the requested node exists on the Panel.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $node = $this->route()->parameter('node');

        return $node instanceof Node && $node->exists;
    }
}
