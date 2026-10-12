<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Pterodactyl\Contracts\Servers\UpdatesServerDockerImage;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Server;

final readonly class UpdateServerDockerImage implements UpdatesServerDockerImage
{
    public function update(Server $server, string $image): Server
    {
        $original = $server->image;
        $server->forceFill(['image' => $image])->saveOrFail();

        if ($original !== $image) {
            Activity::event('server:startup.image')
                ->subject($server)
                ->property(['old' => $original, 'new' => $image])
                ->log();
        }

        return $server;
    }
}
