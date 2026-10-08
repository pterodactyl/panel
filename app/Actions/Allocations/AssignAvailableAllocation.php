<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Illuminate\Support\Collection;
use Pterodactyl\Contracts\Allocations\AssignsAvailableAllocations;
use Pterodactyl\Contracts\Allocations\CreatesAllocations;
use Pterodactyl\Data\AllocationRange;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Allocation\AutoAllocationNotEnabledException;
use Pterodactyl\Exceptions\Service\Allocation\NoAutoAllocationSpaceAvailableException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;
use UnexpectedValueException;

final readonly class AssignAvailableAllocation implements AssignsAvailableAllocations
{
    public function __construct(private CreatesAllocations $allocations) {}

    /** @throws DisplayException */
    public function assignAvailable(Server $server): Allocation
    {
        throw_unless(config('pterodactyl.client_features.allocations.enabled'), AutoAllocationNotEnabledException::class);
        $primaryAllocation = $server->allocation ?? throw new UnexpectedValueException('The server does not have a primary allocation.');
        $allocation = $server->node->allocations()->lockForUpdate()->where('ip', $primaryAllocation->ip)->whereNull('server_id')->inRandomOrder()->first();

        if ($allocation === null) {
            $start = config('pterodactyl.client_features.allocations.range_start');
            $end = config('pterodactyl.client_features.allocations.range_end');
            JsonValueGuard::assertValue($start);
            JsonValueGuard::assertValue($end);
            $range = AllocationRange::fromConfig($start, $end);
            throw_unless($range instanceof AllocationRange, NoAutoAllocationSpaceAvailableException::class);
            $ports = $server->node->allocations()->where('ip', $primaryAllocation->ip)->whereBetween('port', [$range->start, $range->end])->get(['port'])->map(fn (Allocation $allocation): int => $allocation->port)->all();
            $available = array_diff(range($range->start, $range->end), $ports);
            throw_if($available === [], NoAutoAllocationSpaceAvailableException::class);
            $port = Collection::make($available)->random();
            $this->allocations->create($server->node, ['allocation_ip' => $primaryAllocation->ip, 'allocation_ports' => [$port]]);
            $allocation = $server->node->allocations()->lockForUpdate()->where('ip', $primaryAllocation->ip)->where('port', $port)->firstOrFail();
        }

        $allocation->update(['server_id' => $server->id]);

        return $allocation;
    }
}
