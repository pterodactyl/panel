<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Throwable;

interface UpdatesServerStartup
{
    /**
     * @param  ServerStartupModificationData  $data
     *
     * @throws Throwable
     */
    public function update(Server $server, array $data, int $userLevel = User::USER_LEVEL_USER): Server;
}
