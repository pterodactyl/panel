<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Server;
use Throwable;

interface UpdatesServerDockerImage
{
    /**
     * Set the Docker image a server runs on without touching its startup command
     * or install script settings. The image is not checked against the egg, so
     * authorize and validate it first.
     *
     * @throws Throwable
     */
    public function update(Server $server, string $image): Server;
}
