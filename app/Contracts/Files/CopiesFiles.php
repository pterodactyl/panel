<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Models\Server;

interface CopiesFiles
{
    public function copy(Server $server, string $location): void;
}
