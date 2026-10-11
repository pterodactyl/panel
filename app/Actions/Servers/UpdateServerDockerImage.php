<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Pterodactyl\Contracts\Servers\UpdatesServerDockerImage;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Server;
use Throwable;

final readonly class UpdateServerDockerImage implements UpdatesServerDockerImage
{
    /**
     * Set the Docker image a server runs on without touching its startup command
     * or install script settings. The image is not checked against the egg, so
     * authorize and validate it first.
     *
     * @throws Throwable
     */
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
