<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Nodes;

use Pterodactyl\Models\Node;

interface CreatesNodes
{
    /**
     * The node gets a fresh daemon token pair. The input is validated here, and a node given no
     * daemon_base keeps its files in /var/lib/pterodactyl/volumes. Extension field values go
     * under `extensions` as ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  name, location_id, fqdn, scheme, memory, memory_overallocate, disk, disk_overallocate, daemon_listen and daemon_sftp, and optionally public, description, behind_proxy, maintenance_mode, upload_size and daemon_base
     */
    public function create(array $input): Node;
}
