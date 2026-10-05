<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Models\DatabaseHost;

class DynamicDatabaseConnection
{
    public const string DB_CHARSET = 'utf8';

    public const string DB_COLLATION = 'utf8_unicode_ci';

    public const string DB_DRIVER = 'mysql';

    /**
     * DynamicDatabaseConnection constructor.
     */
    public function __construct(
        protected ConfigRepository $config,
        protected Encrypter $encrypter,
    ) {}

    /**
     * Adds a dynamic database connection entry to the runtime config.
     *
     * @throws ModelNotFoundException
     */
    public function set(string $connection, DatabaseHost|int $host, string $database = 'mysql'): void
    {
        if (! $host instanceof DatabaseHost) {
            $host = DatabaseHost::query()->findOrFail($host);
        }

        $this->config->set('database.connections.'.$connection, [
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
