<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\DeletesFiles;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class DeleteFiles implements DeletesFiles
{
    /** @param array<array-key, ApiValue9> $files */
    public function delete(Server $server, ?string $root, array $files): void
    {
        Daemon::server($server)->files()->deleteFiles($root, $files);
    }
}
