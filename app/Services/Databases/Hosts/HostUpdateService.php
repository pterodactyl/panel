<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Databases\Hosts;

use Pterodactyl\Models\DatabaseHost;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class HostUpdateService
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
     * @var \Pterodactyl\Extensions\DynamicDatabaseConnection
     */
    private $dynamic;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    private $repository;

    /**
     * DatabaseHostService constructor.
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
     * Update a database host and persist to the database.
     *
     * @throws \Throwable
     */
    public function handle(int $hostId, array $data): DatabaseHost
    {
        if (!empty(array_get($data, 'password'))) {
            $data['password'] = $this->encrypter->encrypt($data['password']);
        } else {
            unset($data['password']);
        }

        return $this->connection->transaction(function () use ($data, $hostId) {
            $host = $this->repository->update($hostId, $data);
            $this->dynamic->set('dynamic', $host);
            $this->databaseManager->connection('dynamic')->select('SELECT 1 FROM dual');

            return $host;
        });
    }
}
