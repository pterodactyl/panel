<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Throwable;

interface DeletesServers
{
    public function withForce(bool $bool = true): self;

    /**
     * @throws Throwable
     * @throws DisplayException
     */
    public function delete(Server $server): void;
}
