<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use Illuminate\Support\Collection;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Server;

class MountListService
{
    /**
     * @return Collection<int, Mount>
     */
    public function handle(Server $server): Collection
    {
        return Mount::query()->availableToServer($server)->get();
    }
}
