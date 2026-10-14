<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\RenamesFiles;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class RenameFiles implements RenamesFiles
{
    public function rename(Server $server, ?string $root, array $files): void
    {
        Daemon::server($server)->files()->renameFiles($root, $files);

        Activity::event('server:file.rename')->subject($server)->property(['directory' => $root, 'files' => $files])->log();
    }
}
