<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\DecompressesFiles;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class DecompressFile implements DecompressesFiles
{
    public function decompress(Server $server, ?string $root, string $file): void
    {
        set_time_limit(300);
        Daemon::server($server)->files()->decompressFile($root, $file);

        Activity::event('server:file.decompress')->subject($server)->property(['directory' => $root, 'files' => $file])->log();
    }
}
