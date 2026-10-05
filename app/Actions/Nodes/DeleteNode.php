<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Nodes;

use Pterodactyl\Contracts\Nodes\DeletesNodes;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\Node;

final class DeleteNode implements DeletesNodes
{
    /**
     * Delete a node from the panel if no servers are attached to it.
     *
     * @throws HasActiveServersException
     */
    public function delete(Node $node): void
    {
        if ($node->servers()->exists()) {
            throw new HasActiveServersException(trans('exceptions.node.servers_attached'));
        }

        $node->delete();
    }
}
