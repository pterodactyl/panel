<?php

namespace App\Services\Servers;

use App\Models\Server;
use Illuminate\Support\Arr;
use App\Exceptions\DisplayException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use App\Exceptions\Repository\RecordNotFoundException;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Contracts\Repository\AllocationRepositoryInterface;
use App\Exceptions\Http\Connection\DaemonConnectionException;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class BuildModificationService
{
    /**
     * @var \App\Contracts\Repository\AllocationRepositoryInterface
     */
    private $allocationRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \App\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonServerRepository;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * BuildModificationService constructor.
     *
     * @param \App\Contracts\Repository\AllocationRepositoryInterface    $allocationRepository
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \App\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonServerRepository
     * @param \App\Contracts\Repository\ServerRepositoryInterface        $repository
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
     * @param \App\Models\Server $server
     * @param array                      $data
     * @return \App\Models\Server
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
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
            'oom_disabled' => Arr::get($data, 'oom_disabled'),
            'memory' => Arr::get($data, 'memory'),
            'swap' => Arr::get($data, 'swap'),
            'io' => Arr::get($data, 'io'),
            'cpu' => Arr::get($data, 'cpu'),
            'disk' => Arr::get($data, 'disk'),
            'allocation_id' => Arr::get($data, 'allocation_id'),
            'database_limit' => Arr::get($data, 'database_limit'),
            'allocation_limit' => Arr::get($data, 'allocation_limit'),
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
     * @param \App\Models\Server $server
     * @param array                      $data
     *
     * @throws \App\Exceptions\DisplayException
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
