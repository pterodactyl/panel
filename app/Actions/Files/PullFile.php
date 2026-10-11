<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\PullsFiles;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class PullFile implements PullsFiles
{
    /** @param array<mixed> $options */
    public function pull(Server $server, string $url, ?string $directory, array $options): void
    {
        Daemon::server($server)->files()->pull($url, $directory, $options);

        Activity::event('server:file.pull')->subject($server)->property(['directory' => $directory, 'url' => $url])->log();
    }
}
