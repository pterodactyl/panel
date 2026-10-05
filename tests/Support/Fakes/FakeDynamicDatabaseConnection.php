<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\DatabaseHost;
use Throwable;

class FakeDynamicDatabaseConnection extends DynamicDatabaseConnection
{
    public ?Throwable $throwOnSet = null;

    /** @var list<array{connection: string, host: DatabaseHost|int, database: string}> */
    public array $setCalls = [];

    public function __construct() {}

    public function set(string $connection, DatabaseHost|int $host, string $database = 'mysql'): void
    {
        $this->setCalls[] = [
            'connection' => $connection,
            'host' => $host,
            'database' => $database,
        ];

        if ($this->throwOnSet !== null) {
            throw $this->throwOnSet;
        }
    }
}
