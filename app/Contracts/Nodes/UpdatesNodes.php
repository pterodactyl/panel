<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Nodes;

use Pterodactyl\Models\Node;
use Throwable;

interface UpdatesNodes
{
    /**
     * Update the configuration values for a given node on the machine.
     *
     * @param  NodeUpdateData  $data
     *
     * @throws Throwable
     */
    public function update(Node $node, array $data, bool $resetToken = false): Node;
}
