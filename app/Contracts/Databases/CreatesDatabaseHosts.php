<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Pterodactyl\Models\DatabaseHost;
use Throwable;

interface CreatesDatabaseHosts
{
    /**
     * The host is saved once the Panel has connected to it with the given credentials. The input
     * is validated here and the password is stored encrypted; a host given no node_id is global.
     * Extension field values go under `extensions` as ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  name, host, port and username, and optionally password and node_id
     *
     * @throws Throwable
     */
    public function create(array $input): DatabaseHost;
}
