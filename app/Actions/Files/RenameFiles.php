<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\RenamesFiles;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class RenameFiles implements RenamesFiles
{
    /** @param array<array-key, ApiValue9> $files */
    public function rename(Server $server, ?string $root, array $files): void
    {
        Daemon::server($server)->files()->renameFiles($root, $files);
    }
}
