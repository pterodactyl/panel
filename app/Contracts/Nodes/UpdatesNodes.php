<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Nodes;

use Pterodactyl\Exceptions\Service\Node\ConfigurationNotPersistedException;
use Pterodactyl\Models\Node;
use Throwable;

interface UpdatesNodes
{
    /**
     * A new daemon token pair is minted when $resetToken is set. The input is validated here and
     * takes the fields a node is created with, required ones included; a node given no
     * daemon_base goes back to /var/lib/pterodactyl/volumes, and the other optional fields keep
     * their stored values when they are left out. Extension field values go under `extensions`
     * as ValidatedExtensionValues. When Wings cannot be reached the change is still saved and
     * ConfigurationNotPersistedException is thrown.
     *
     * @param  array<string, mixed>  $input  the fields a node is created with
     *
     * @throws ConfigurationNotPersistedException
     * @throws Throwable
     */
    public function update(Node $node, array $input, bool $resetToken = false): Node;
}
