<?php

namespace Pterodactyl\Extensions;

use Pterodactyl\Models\DatabaseHost;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class DynamicDatabaseConnection
{
    public const DB_CHARSET = 'utf8';
    public const DB_COLLATION = 'utf8_unicode_ci';
    public const DB_DRIVER = 'mysql';

    /**
     * DynamicDatabaseConnection constructor.
     */
    public function __construct(
        protected ConfigRepository $config,
        protected Encrypter $encrypter,
        protected DatabaseHostRepositoryInterface $repository,
    ) {
    }

    /**
     * Adds a dynamic database connection entry to the runtime config.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function set(string $connection, DatabaseHost|int $host, string $database = 'mysql'): void
    {
        if (!$host instanceof DatabaseHost) {
            $host = $this->repository->find($host);
        }

        $this->config->set('database.connections.' . $connection, [
            'driver' => self::DB_DRIVER,
            'host' => $host->host,
            'port' => $host->port,
            'database' => $database,
            'username' => $host->username,
            'password' => $this->encrypter->decrypt($host->password),
            'charset' => self::DB_CHARSET,
            'collation' => self::DB_COLLATION,
        ]);
    }
}
