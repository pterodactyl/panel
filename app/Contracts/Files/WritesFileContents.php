<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Models\Server;

interface WritesFileContents
{
    public function write(Server $server, string $path, string $contents): void;
}
