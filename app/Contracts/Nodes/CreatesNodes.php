<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Nodes;

use Pterodactyl\Models\Node;

interface CreatesNodes
{
    /**
     * Create a node and mint its daemon token pair. The input is validated here, so it can
     * come straight from any caller; extension field values go under `extensions` as
     * ValidatedExtensionValues. A node given no daemon_base keeps its files in
     * /var/lib/pterodactyl/volumes.
     *
     * @param  array<string, mixed>  $input  name, location_id, fqdn, scheme, memory, memory_overallocate, disk, disk_overallocate, daemon_listen and daemon_sftp, and optionally public, description, behind_proxy, maintenance_mode, upload_size and daemon_base
     */
    public function create(array $input): Node;
}
