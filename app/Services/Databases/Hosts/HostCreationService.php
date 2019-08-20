<?php

namespace App\Services\Databases\Hosts;

use Illuminate\Support\Arr;
use App\Models\DatabaseHost;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use App\Extensions\DynamicDatabaseConnection;
use App\Contracts\Repository\DatabaseHostRepositoryInterface;

class HostCreationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    private $databaseManager;

    /**
     * @var \App\Extensions\DynamicDatabaseConnection
     */
    private $dynamic;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \App\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    private $repository;

    /**
     * HostCreationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                          $connection
     * @param \Illuminate\Database\DatabaseManager                              $databaseManager
     * @param \App\Contracts\Repository\DatabaseHostRepositoryInterface $repository
     * @param \App\Extensions\DynamicDatabaseConnection                 $dynamic
     * @param \Illuminate\Contracts\Encryption\Encrypter                        $encrypter
     */
    public function __construct(
        ConnectionInterface $connection,
        DatabaseManager $databaseManager,
        DatabaseHostRepositoryInterface $repository,
        DynamicDatabaseConnection $dynamic,
        Encrypter $encrypter
    ) {
        $this->connection = $connection;
        $this->databaseManager = $databaseManager;
        $this->dynamic = $dynamic;
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Create a new database host on the Panel.
     *
     * @param array $data
     * @return \App\Models\DatabaseHost
     *
     * @throws \Throwable
     */
    public function handle(array $data): DatabaseHost
    {
        return $this->connection->transaction(function () use ($data) {
            $host = $this->repository->create([
                'password' => $this->encrypter->encrypt(Arr::get($data, 'password')),
                'name' => Arr::get($data, 'name'),
                'host' => Arr::get($data, 'host'),
                'port' => Arr::get($data, 'port'),
                'username' => Arr::get($data, 'username'),
                'max_databases' => null,
                'node_id' => Arr::get($data, 'node_id'),
            ]);

            // Confirm access using the provided credentials before saving data.
            $this->dynamic->set('dynamic', $host);
            $this->databaseManager->connection('dynamic')->select('SELECT 1 FROM dual');

            return $host;
        });
    }
}
