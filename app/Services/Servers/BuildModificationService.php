<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Log\Writer;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class BuildModificationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $allocationRepository;

    /**
     * @var array
     */
    protected $build = [];

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $database;

    /**
     * @var null|int
     */
    protected $firstAllocationId = null;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * BuildModificationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface    $allocationRepository
     * @param \Illuminate\Database\ConnectionInterface                           $database
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface        $repository
     * @param \Illuminate\Log\Writer                                             $writer
     */
    public function __construct(
        AllocationRepositoryInterface $allocationRepository,
        ConnectionInterface $database,
        DaemonServerRepositoryInterface $daemonServerRepository,
        ServerRepositoryInterface $repository,
        Writer $writer
    ) {
        $this->allocationRepository = $allocationRepository;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->database = $database;
        $this->repository = $repository;
        $this->writer = $writer;
    }

    /**
     * Set build array parameters.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setBuild($key, $value)
    {
        $this->build[$key] = $value;
    }

    /**
     * Return the build array or an item out of the build array.
     *
     * @param string|null $attribute
     * @return array|mixed|null
     */
    public function getBuild($attribute = null)
    {
        if (is_null($attribute)) {
            return $this->build;
        }

        return array_get($this->build, $attribute);
    }

    /**
     * Change the build details for a specified server.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param array                          $data
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($server, array $data)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        $data['allocation_id'] = array_get($data, 'allocation_id', $server->allocation_id);
        $this->database->beginTransaction();

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

            $this->setBuild('default', ['ip' => $allocation->ip, 'port' => $allocation->port]);
        }

        $server = $this->repository->update($server->id, [
            'memory' => (int) array_get($data, 'memory', $server->memory),
            'swap' => (int) array_get($data, 'swap', $server->swap),
            'io' => (int) array_get($data, 'io', $server->io),
            'cpu' => (int) array_get($data, 'cpu', $server->cpu),
            'disk' => (int) array_get($data, 'disk', $server->disk),
            'allocation_id' => array_get($data, 'allocation_id', $server->allocation_id),
        ]);

        $allocations = $this->allocationRepository->findWhere([
            ['server_id', '=', $server->id],
        ]);

        $this->setBuild('memory', (int) $server->memory);
        $this->setBuild('swap', (int) $server->swap);
        $this->setBuild('io', (int) $server->io);
        $this->setBuild('cpu', (int) $server->cpu);
        $this->setBuild('disk', (int) $server->disk);
        $this->setBuild('ports|overwrite', $allocations->groupBy('ip')->map(function ($item) {
            return $item->pluck('port');
        })->toArray());

        try {
            $this->daemonServerRepository->setServer($server)->update([
                'build' => $this->getBuild(),
            ]);

            $this->database->commit();
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }
    }

    /**
     * Process the allocations being assigned in the data and ensure they are available for a server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param array                      $data
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function processAllocations(Server $server, array &$data)
    {
        if (! array_key_exists('add_allocations', $data) && ! array_key_exists('remove_allocations', $data)) {
            return;
        }

        // Loop through allocations to add.
        if (array_key_exists('add_allocations', $data) && ! empty($data['add_allocations'])) {
            $unassigned = $this->allocationRepository->findWhere([
                ['server_id', '=', null],
                ['node_id', '=', $server->node_id],
            ])->pluck('id')->toArray();

            foreach ($data['add_allocations'] as $allocation) {
                if (! in_array($allocation, $unassigned)) {
                    continue;
                }

                $this->firstAllocationId = $this->firstAllocationId ?? $allocation;
                $toUpdate[] = [$allocation];
            }

            if (isset($toUpdate)) {
                $this->allocationRepository->updateWhereIn('id', $toUpdate, ['server_id' => $server->id]);
                unset($toUpdate);
            }
        }

        // Loop through allocations to remove.
        if (array_key_exists('remove_allocations', $data) && ! empty($data['remove_allocations'])) {
            $assigned = $this->allocationRepository->findWhere([
                ['server_id', '=', $server->id],
            ])->pluck('id')->toArray();

            foreach ($data['remove_allocations'] as $allocation) {
                if (! in_array($allocation, $assigned)) {
                    continue;
                }

                if ($allocation == $data['allocation_id']) {
                    if (is_null($this->firstAllocationId)) {
                        throw new DisplayException(trans('admin/server.exceptions.no_new_default_allocation'));
                    }

                    $data['allocation_id'] = $this->firstAllocationId;
                }

                $toUpdate[] = [$allocation];
            }

            if (isset($toUpdate)) {
                $this->allocationRepository->updateWhereIn('id', $toUpdate, ['server_id' => null]);
                unset($toUpdate);
            }
        }
    }
}
