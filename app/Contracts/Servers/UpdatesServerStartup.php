<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Throwable;

interface UpdatesServerStartup
{
    /**
     * Only the fields given are validated here and changed. The environment is checked against
     * the egg's variable rules, and below the admin level only the variables a user may edit are
     * written. Only an administrator changes the startup command, egg, Docker image and install
     * script flag; at the admin level an install script flag left out turns it off.
     *
     * @param  array<string, mixed>  $input  any of environment and, at the admin level, startup, egg_id, docker_image and skip_scripts
     *
     * @throws Throwable
     */
    public function update(Server $server, array $input, int $userLevel = User::USER_LEVEL_USER): Server;
}
