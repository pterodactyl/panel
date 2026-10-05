<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Nodes;

use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\Node;

interface DeletesNodes
{
    /**
     * Delete a node from the panel if no servers are attached to it.
     *
     * @throws HasActiveServersException
     */
    public function delete(Node $node): void;
}
