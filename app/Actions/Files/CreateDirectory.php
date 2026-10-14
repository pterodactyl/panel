<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\CreatesDirectories;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class CreateDirectory implements CreatesDirectories
{
    public function create(Server $server, string $name, string $root): void
    {
        Daemon::server($server)->files()->createDirectory($name, $root);

        Activity::event('server:file.create-directory')->subject($server)->property(['name' => $name, 'directory' => $root])->log();
    }
}
