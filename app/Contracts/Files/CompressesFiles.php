<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Models\Server;

interface CompressesFiles
{
    /**
     * @param  array<mixed>  $files
     * @return DaemonFileObject
     */
    public function compress(Server $server, ?string $root, array $files): array;
}
