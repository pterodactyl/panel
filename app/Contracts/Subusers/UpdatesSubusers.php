<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Subusers;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Throwable;

interface UpdatesSubusers
{
    /**
     * Replaces the subuser's permissions and revokes their outstanding SFTP sessions on Wings;
     * permissions the subuser already has, in any order, change nothing. The permissions are
     * validated here: each must be one the panel or an enabled extension defines, or one the
     * subuser already holds. Whether the caller may grant them is left to the caller.
     *
     * @param  list<string>  $permissions
     *
     * @throws Throwable
     */
    public function update(Server $server, Subuser $subuser, array $permissions): Subuser;
}
