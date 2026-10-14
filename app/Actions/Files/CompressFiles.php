<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\CompressesFiles;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class CompressFiles implements CompressesFiles
{
    public function compress(Server $server, ?string $root, array $files): array
    {
        $archive = Daemon::server($server)->files()->compressFiles($root, $files);

        Activity::event('server:file.compress')->subject($server)->property(['directory' => $root, 'files' => $files])->log();

        return $archive;
    }
}
