<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Throwable;

interface UpdatesServerBuild
{
    /**
     * @param  ServerBuildModificationData  $data
     *
     * @throws Throwable
     * @throws DisplayException
     */
    public function update(Server $server, array $data): Server;
}
