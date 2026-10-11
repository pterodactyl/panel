<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Pterodactyl\Models\DatabaseHost;
use Throwable;

interface UpdatesDatabaseHosts
{
    /**
     * Update a database host once the Panel has connected to it with the new credentials.
     * The input is validated here, so it can come straight from any caller. It takes the
     * fields a host is created with, required ones included; a blank password keeps the
     * stored one, and a host given no node_id becomes global. Extension field values go under
     * `extensions` as ValidatedExtensionValues.
     *
     * @param  ActionInput  $input  the fields a host is created with
     *
     * @throws Throwable
     */
    public function update(DatabaseHost $host, array $input): DatabaseHost;
}
