<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Allocations\AssignsAllocations;
use Pterodactyl\Contracts\Allocations\AssignsAvailableAllocations;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Throwable;

final readonly class AssignAllocation implements AssignsAllocations
{
    public function __construct(private AssignsAvailableAllocations $assignable) {}

    /**
     * @throws DisplayException
     * @throws Throwable
     */
    public function assign(Server $server): Allocation
    {
        return DB::transaction(function () use ($server): Allocation {
            throw_if($server->allocations()->lockForUpdate()->count() >= $server->allocation_limit, DisplayException::class, 'Cannot assign additional allocations to this server: limit has been reached.');

            return $this->assignable->assignAvailable($server)->refresh();
        });
    }
}
