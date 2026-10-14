<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\DeletesFiles;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class DeleteFiles implements DeletesFiles
{
    public function delete(Server $server, ?string $root, array $files): void
    {
        Daemon::server($server)->files()->deleteFiles($root, $files);

        Activity::event('server:file.delete')->subject($server)->property(['directory' => $root, 'files' => $files])->log();
    }
}
