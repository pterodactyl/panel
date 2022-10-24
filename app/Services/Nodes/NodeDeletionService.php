<?php

namespace Pterodactyl\Services\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Exceptions\Service\HasActiveServersException;

class NodeDeletionService
{
    /**
     * NodeDeletionService constructor.
     */
    public function __construct(protected Translator $translator)
    {
    }

    /**
     * Delete a node from the panel if no servers are attached to it.
     *
     * @throws HasActiveServersException
     */
    public function handle(int|Node $node): int
    {
        if (is_int($node)) {
            $node = Node::query()->findOrFail($node);
        }

        $servers = $node->servers()->count();
        if ($servers > 0) {
            throw new HasActiveServersException($this->translator->get('exceptions.node.servers_attached'));
        }

        return $node->delete();
    }
}
