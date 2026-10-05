<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Subusers;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Throwable;

interface DeletesSubusers
{
    /**
     * Removes the subuser from the server and revokes their outstanding SFTP sessions on Wings.
     *
     * @throws Throwable
     */
    public function delete(Server $server, Subuser $subuser): void;
}
