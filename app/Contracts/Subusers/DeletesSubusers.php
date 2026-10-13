<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Subusers;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Throwable;

interface DeletesSubusers
{
    /**
     * The subuser's outstanding SFTP sessions on Wings are revoked.
     *
     * @throws Throwable
     */
    public function delete(Server $server, Subuser $subuser): void;
}
