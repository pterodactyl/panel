<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Throwable;

interface UpdatesServerBuild
{
    /**
     * Update a server's build and sync it to Wings. The input is validated here, so it can
     * come straight from any caller. Only the fields given are changed, except the feature
     * limits: a database or allocation limit left out is cleared, and a backup limit left
     * out becomes 0. Allocations to add are assigned only when they are free and on the
     * server's node; removing the default allocation needs a newly added one to replace it.
     *
     * @param  array<string, mixed>  $input  any of allocation_id, oom_disabled, memory, swap, io, cpu, threads, disk, add_allocations, remove_allocations, database_limit, allocation_limit and backup_limit
     *
     * @throws Throwable
     * @throws DisplayException
     */
    public function update(Server $server, array $input): Server;
}
