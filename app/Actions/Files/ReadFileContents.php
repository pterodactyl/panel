<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\ReadsFileContents;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;

final readonly class ReadFileContents implements ReadsFileContents
{
    public function read(Server $server, string $path, ?int $maxBytes = null): string
    {
        return Daemon::server($server)->files()->getContent(
            $path,
            $maxBytes ?? JsonValueGuard::integer(config('pterodactyl.files.max_edit_size'))
        );
    }
}
