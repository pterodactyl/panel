<?php

namespace Pterodactyl\Services\Allocations;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Allocation\AutoAllocationNotEnabledException;
use Pterodactyl\Exceptions\Service\Allocation\NoAutoAllocationSpaceAvailableException;

class FindAssignableAllocationService
{
    /**
     * @var \Pterodactyl\Services\Allocations\AssignmentService
     */
    private $service;

    /**
     * FindAssignableAllocationService constructor.
     *
     * @param \Pterodactyl\Services\Allocations\AssignmentService $service
     */
    public function __construct(AssignmentService $service)
    {
        $this->service = $service;
    }

    /**
     * Finds an existing unassigned allocation and attempts to assign it to the given server. If
     * no allocation can be found, a new one will be created with a random port between the defined
     * range from the configuration.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \Pterodactyl\Models\Allocation
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    public function handle(Server $server)
    {
        if (!config('pterodactyl.client_features.allocations.enabled')) {
            throw new AutoAllocationNotEnabledException;
        }

        if ($server->allocations()->count() >= $server->allocation_limit) {
            throw new DisplayException(
                'Cannot assign additional allocations to this server: limit has been reached.'
            );
        }

        // Attempt to find a given available allocation for a server. If one cannot be found
        // we will fall back to attempting to create a new allocation that can be used for the
        // server.
        /** @var \Pterodactyl\Models\Allocation|null $allocation */
        $allocation = $server->node->allocations()
            ->where('ip', $server->allocation->ip)
            ->whereNull('server_id')
            ->inRandomOrder()
            ->first();

        $allocation = $allocation ?? $this->createNewAllocation($server);

        $allocation->update(['server_id' => $server->id]);

        return $allocation->refresh();
    }

    /**
     * @param \Pterodactyl\Models\Server $server
     * @return \Pterodactyl\Models\Allocation
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    protected function createNewAllocation(Server $server): Allocation
    {
        $start = config('pterodactyl.client_features.allocations.range_start', null);
        $end = config('pterodactyl.client_features.allocations.range_end', null);

        if (!$start || !$end) {
            throw new NoAutoAllocationSpaceAvailableException;
        }

        Assert::integerish($start);
        Assert::integerish($end);

        // Get all of the currently allocated ports for the node so that we can figure out
        // which port might be available.
        $ports = $server->node->allocations()
            ->where('ip', $server->allocation->ip)
            ->whereBetween('port', [$start, $end])
            ->pluck('port');

        // Compute the difference of the range and the currently created ports, finding
        // any port that does not already exist in the database. We will then use this
        // array of ports to create a new allocation to assign to the server.
        $available = array_diff(range($start, $end), $ports->toArray());

        // If we've already allocated all of the ports, just abort.
        if (empty($available)) {
            throw new NoAutoAllocationSpaceAvailableException;
        }

        // Pick a random port out of the remaining available ports.
        /** @var int $port */
        $port = $available[array_rand($available)];

        $this->service->handle($server->node, [
            'allocation_ip' => $server->allocation->ip,
            'allocation_ports' => [$port],
        ]);

        /** @var \Pterodactyl\Models\Allocation $allocation */
        $allocation = $server->node->allocations()
            ->where('ip', $server->allocation->ip)
            ->where('port', $port)
            ->firstOrFail();

        return $allocation;
    }
}