<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;

interface TogglesServerInstallStatus
{
    /**
     * Flip a server between installed and installing. A server whose install
     * failed must be reinstalled instead.
     *
     * @throws DisplayException
     */
    public function toggle(Server $server): void;
}
