<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Server;
use Throwable;

interface UpdatesServerDetails
{
    /**
     * Update a server's details. The input is validated here, so it can come straight from
     * any caller. Every detail is replaced: an external id left out is cleared and a
     * description left out becomes empty. A new owner takes the server's SFTP access from
     * the previous one. Extension field values go under `extensions` as
     * ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  name and owner_id, and optionally external_id and description
     *
     * @throws Throwable
     */
    public function update(Server $server, array $input): Server;
}
