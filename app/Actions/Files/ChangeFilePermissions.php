<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\ChangesFilePermissions;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class ChangeFilePermissions implements ChangesFilePermissions
{
    /** @param array<array-key, ApiValue9> $files */
    public function change(Server $server, ?string $root, array $files): void
    {
        Daemon::server($server)->files()->chmodFiles($root, $files);
    }
}
