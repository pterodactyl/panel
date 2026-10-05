<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;

class ServerFinderService
{
    public function byExternalId(string $externalId): Server
    {
        return Server::query()->where('external_id', $externalId)->firstOrFail();
    }
}
