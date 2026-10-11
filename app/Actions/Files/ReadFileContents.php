<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Illuminate\Support\Fluent;
use Pterodactyl\Contracts\Files\ReadsFileContents;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class ReadFileContents implements ReadsFileContents
{
    public function read(Server $server, string $path, ?int $maxBytes = null): string
    {
        return Daemon::server($server)->files()->getContent(
            $path,
            $maxBytes ?? Fluent::make(config()->array('pterodactyl.files'))->integer('max_edit_size')
        );
    }
}
