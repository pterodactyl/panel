<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Nodes;

use Pterodactyl\Models\Node;
use Throwable;

interface UpdatesNodes
{
    /**
     * Update the configuration values for a given node on the machine, minting a new daemon
     * token pair when $resetToken is set.
     *
     * The input is validated here, so it can come straight from any caller. It takes the
     * fields a node is created with, required ones included; a node given no daemon_base goes
     * back to /var/lib/pterodactyl/volumes, and the other optional fields keep their stored
     * values when they are left out. Extension field values go under `extensions` as
     * ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  the fields a node is created with
     *
     * @throws Throwable
     */
    public function update(Node $node, array $input, bool $resetToken = false): Node;
}
