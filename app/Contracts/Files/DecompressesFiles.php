<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Models\Server;

interface DecompressesFiles
{
    public function decompress(Server $server, ?string $root, string $file): void;
}
