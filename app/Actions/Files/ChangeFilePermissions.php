<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\ChangesFilePermissions;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class ChangeFilePermissions implements ChangesFilePermissions
{
    /** @param array<mixed> $files */
    public function change(Server $server, ?string $root, array $files): void
    {
        Daemon::server($server)->files()->chmodFiles($root, $files);

        Activity::event('server:file.chmod')->subject($server)->property(['directory' => $root, 'files' => $files])->log();
    }
}
