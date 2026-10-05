<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Subusers;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Throwable;

interface UpdatesSubusers
{
    /**
     * Replaces the subuser's permissions and revokes their outstanding SFTP sessions on Wings.
     *
     * @param  list<string>  $permissions
     *
     * @throws Throwable
     */
    public function update(Server $server, Subuser $subuser, array $permissions): Subuser;
}
