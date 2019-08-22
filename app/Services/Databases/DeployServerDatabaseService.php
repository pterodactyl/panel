<?php

namespace App\Services\Databases;

use App\Models\Server;
use App\Models\Database;
use Illuminate\Support\Arr;
use App\Contracts\Repository\DatabaseRepositoryInterface;
use App\Contracts\Repository\DatabaseHostRepositoryInterface;
use App\Exceptions\Service\Database\TooManyDatabasesException;
use App\Exceptions\Service\Database\NoSuitableDatabaseHostException;
use App\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;

class DeployServerDatabaseService
{
    /**
     * @var \App\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    private $databaseHostRepository;

    /**
     * @var \App\Services\Databases\DatabaseManagementService
     */
    private $managementService;

    /**
     * @var \App\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * ServerDatabaseCreationService constructor.
     *
     * @param \App\Contracts\Repository\DatabaseRepositoryInterface     $repository
     * @param \App\Contracts\Repository\DatabaseHostRepositoryInterface $databaseHostRepository
     * @param \App\Services\Databases\DatabaseManagementService         $managementService
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
     * @param \App\Models\Server $server
     * @param array                      $data
     * @return \App\Models\Database
     *
     * @throws \App\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
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
            'database' => Arr::get($data, 'database'),
            'remote' => Arr::get($data, 'remote'),
        ]);
    }
}
