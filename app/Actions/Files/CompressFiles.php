<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\CompressesFiles;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class CompressFiles implements CompressesFiles
{
    /**
     * @param  array<array-key, ApiValue9>  $files
     * @return DaemonFileObject
     */
    public function compress(Server $server, ?string $root, array $files): array
    {
        return Daemon::server($server)->files()->compressFiles($root, $files);
    }
}
