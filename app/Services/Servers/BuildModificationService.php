<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class BuildModificationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    private $allocationRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonServerRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * BuildModificationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface    $allocationRepository
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface        $repository
     */
    public function __construct(
        AllocationRepositoryInterface $allocationRepository,
        ConnectionInterface $connection,
        DaemonServerRepositoryInterface $daemonServerRepository,
        ServerRepositoryInterface $repository
    ) {
        $this->allocationRepository = $allocationRepository;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->connection = $connection;
        $this->repository = $repository;
    }

    /**
     * Change the build details for a specified server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param array                      $data
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Server $server, array $data)
    {
        $build = [];
        $this->connection->beginTransaction();

        $this->processAllocations($server, $data);
        if (isset($data['allocation_id']) && $data['allocation_id'] != $server->allocation_id) {
            try {
                $allocation = $this->allocationRepository->findFirstWhere([
                    ['id', '=', $data['allocation_id']],
                    ['server_id', '=', $server->id],
                ]);
            } catch (RecordNotFoundException $ex) {
                throw new DisplayException(trans('admin/server.exceptions.default_allocation_not_found'));
            }

            $build['default'] = ['ip' => $allocation->ip, 'port' => $allocation->port];
        }

        $server = $this->repository->withFreshModel()->update($server->id, [
            'oom_disabled' => array_get($data, 'oom_disabled'),
            'memory' => array_get($data, 'memory'),
            'swap' => array_get($data, 'swap'),
            'io' => array_get($data, 'io'),
            'cpu' => array_get($data, 'cpu'),
            'disk' => array_get($data, 'disk'),
            'allocation_id' => array_get($data, 'allocation_id'),
            'database_limit' => array_get($data, 'database_limit'),
            'allocation_limit' => array_get($data, 'allocation_limit'),
        ]);

        $allocations = $this->allocationRepository->findWhere([['server_id', '=', $server->id]]);

        $build['oom_disabled'] = $server->oom_disabled;
        $build['memory'] = (int) $server->memory;
        $build['swap'] = (int) $server->swap;
        $build['io'] = (int) $server->io;
        $build['cpu'] = (int) $server->cpu;
        $build['disk'] = (int) $server->disk;
        $build['ports|overwrite'] = $allocations->groupBy('ip')->map(function ($item) {
            return $item->pluck('port');
        })->toArray();

        try {
            $this->daemonServerRepository->setServer($server)->update(['build' => $build]);
            $this->connection->commit();
        } catch (RequestException $exception) {
            throw new DaemonConnectionException($exception);
        }

        return $server;
    }

    /**
     * Process the allocations being assigned in the data and ensure they
     * are available for a server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param array                      $data
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    private function processAllocations(Server $server, array &$data)
    {
        $firstAllocationId = null;

        if (! array_key_exists('add_allocations', $data) && ! array_key_exists('remove_allocations', $data)) {
            return;
        }

        // Handle the addition of allocations to this server.
        if (array_key_exists('add_allocations', $data) && ! empty($data['add_allocations'])) {
            $unassigned = $this->allocationRepository->getUnassignedAllocationIds($server->node_id);

            $updateIds = [];
            foreach ($data['add_allocations'] as $allocation) {
                if (! in_array($allocation, $unassigned)) {
                    continue;
                }

                $firstAllocationId = $firstAllocationId ?? $allocation;
                $updateIds[] = $allocation;
            }

            if (! empty($updateIds)) {
                $this->allocationRepository->updateWhereIn('id', $updateIds, ['server_id' => $server->id]);
            }
        }

        // Handle removal of allocations from this server.
        if (array_key_exists('remove_allocations', $data) && ! empty($data['remove_allocations'])) {
            $assigned = $this->allocationRepository->getAssignedAllocationIds($server->id);

            $updateIds = [];
            foreach ($data['remove_allocations'] as $allocation) {
                if (! in_array($allocation, $assigned)) {
                    continue;
                }

                if ($allocation == $data['allocation_id']) {
                    if (is_null($firstAllocationId)) {
                        throw new DisplayException(trans('admin/server.exceptions.no_new_default_allocation'));
                    }

                    $data['allocation_id'] = $firstAllocationId;
                }

                $updateIds[] = $allocation;
            }

            if (! empty($updateIds)) {
                $this->allocationRepository->updateWhereIn('id', $updateIds, ['server_id' => null]);
            }
        }
    }
}
