<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Server;
use Throwable;

interface UpdatesServerDetails
{
    /**
     * @param  ModelAttributes  $data
     *
     * @throws Throwable
     */
    public function update(Server $server, array $data): Server;
}
