<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Pterodactyl\Models\DatabaseHost;
use Throwable;

interface CreatesDatabaseHosts
{
    /**
     * Create a new database host once the Panel has connected to it with the given
     * credentials. The input is validated here, so it can come straight from any caller;
     * extension field values go under `extensions` as ValidatedExtensionValues. The password
     * is stored encrypted, and a host given no node_id is global.
     *
     * @param  array<string, mixed>  $input  name, host, port and username, and optionally password and node_id
     *
     * @throws Throwable
     */
    public function create(array $input): DatabaseHost;
}
