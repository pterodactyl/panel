<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\DatabaseHost;

class DynamicDatabaseConnection
{
    public const string DB_CHARSET = 'utf8';

    public const string DB_COLLATION = 'utf8_unicode_ci';

    public const string DB_DRIVER = 'mysql';

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

        Config::set('database.connections.'.$connection, [
            'driver' => self::DB_DRIVER,
            'host' => $host->host,
            'port' => $host->port,
            'database' => $database,
            'username' => $host->username,
            'password' => Crypt::decrypt($host->password),
            'charset' => self::DB_CHARSET,
            'collation' => self::DB_COLLATION,
        ]);

        // The manager caches resolved connections by name, so drop the previous host's
        // connection or the next DB::connection() call keeps talking to it.
        DB::purge($connection);
    }
}
