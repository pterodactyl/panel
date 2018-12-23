<?php

namespace Pterodactyl\Services\Databases;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;
use Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException;
use Pterodactyl\Exceptions\Service\Database\NoSuitableDatabaseHostException;
use Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;

class DeployServerDatabaseService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    private $databaseHostRepository;

    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    private $managementService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * ServerDatabaseCreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface     $repository
     * @param \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface $databaseHostRepository
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService         $managementService
     */
    public function __construct(
        DatabaseRepositoryInterface $repository,
        DatabaseHostRepositoryInterface $databaseHostRepository,
        DatabaseManagementService $managementService
    ) {
        $this->databaseHostRepository = $databaseHostRepository;
        $this->managementService = $managementService;
        $this->repository = $repository;
    }

    /**
     * @param \Pterodactyl\Models\Server $server
     * @param array                      $data
     * @return \Pterodactyl\Models\Database
     *
     * @throws \Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     * @throws \Exception
     */
    public function handle(Server $server, array $data): Database
    {
        if (! config('pterodactyl.client_features.databases.enabled')) {
            throw new DatabaseClientFeatureNotEnabledException;
        }

        $databases = $this->repository->findCountWhere([['server_id', '=', $server->id]]);
        if (! is_null($server->database_limit) && $databases >= $server->database_limit) {
            throw new TooManyDatabasesException;
        }

        $allowRandom = config('pterodactyl.client_features.databases.allow_random');
        $hosts = $this->databaseHostRepository->setColumns(['id'])->findWhere([
            ['node_id', '=', $server->node_id],
        ]);

        if ($hosts->isEmpty() && ! $allowRandom) {
            throw new NoSuitableDatabaseHostException;
        }

        if ($hosts->isEmpty()) {
            $hosts = $this->databaseHostRepository->setColumns(['id'])->all();
            if ($hosts->isEmpty()) {
                throw new NoSuitableDatabaseHostException;
            }
        }

        $host = $hosts->random();

        return $this->managementService->create($server->id, [
            'database_host_id' => $host->id,
            'database' => array_get($data, 'database'),
            'remote' => array_get($data, 'remote'),
        ]);
    }
}
