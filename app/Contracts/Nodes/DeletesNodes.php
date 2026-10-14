<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Nodes;

use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\Node;

interface DeletesNodes
{
    /**
     * A node with servers on it is not deleted.
     *
     * @throws HasActiveServersException
     */
    public function delete(Node $node): void;
}
