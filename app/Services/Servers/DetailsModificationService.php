<?php

namespace App\Services\Servers;

use App\Models\Server;
use Illuminate\Support\Arr;
use Illuminate\Database\ConnectionInterface;
use App\Traits\Services\ReturnsUpdatedModels;
use App\Repositories\Eloquent\ServerRepository;
use App\Services\DaemonKeys\DaemonKeyCreationService;
use App\Services\DaemonKeys\DaemonKeyDeletionService;

class DetailsModificationService
{
    use ReturnsUpdatedModels;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \App\Services\DaemonKeys\DaemonKeyCreationService
     */
    private $keyCreationService;

    /**
     * @var \App\Services\DaemonKeys\DaemonKeyDeletionService
     */
    private $keyDeletionService;

    /**
     * @var \App\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * DetailsModificationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                  $connection
     * @param \App\Services\DaemonKeys\DaemonKeyCreationService $keyCreationService
     * @param \App\Services\DaemonKeys\DaemonKeyDeletionService $keyDeletionService
     * @param \App\Repositories\Eloquent\ServerRepository       $repository
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonKeyCreationService $keyCreationService,
        DaemonKeyDeletionService $keyDeletionService,
        ServerRepository $repository
    ) {
        $this->connection = $connection;
        $this->keyCreationService = $keyCreationService;
        $this->keyDeletionService = $keyDeletionService;
        $this->repository = $repository;
    }

    /**
     * Update the details for a single server instance.
     *
     * @param \App\Models\Server $server
     * @param array                      $data
     * @return bool|\App\Models\Server
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Server $server, array $data)
    {
        $this->connection->beginTransaction();

        $response = $this->repository->setFreshModel($this->getUpdatedModel())->update($server->id, [
            'external_id' => Arr::get($data, 'external_id'),
            'owner_id' => Arr::get($data, 'owner_id'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description') ?? '',
        ], true, true);

        if ((int) Arr::get($data, 'owner_id', 0) !== (int) $server->owner_id) {
            $this->keyDeletionService->handle($server, $server->owner_id);
            $this->keyCreationService->handle($server->id, Arr::get($data, 'owner_id'));
        }

        $this->connection->commit();

        return $response;
    }
}
