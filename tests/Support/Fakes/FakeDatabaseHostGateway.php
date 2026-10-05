<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use PHPUnit\Framework\Assert;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Throwable;

/**
 * Standalone fake for DatabaseHostGateway.
 *
 * Bypasses the DatabaseManager constructor so tests never touch a real
 * database host. Records every mutating call attempt in $calls (even when
 * $throwOn causes that call to throw, matching Mockery's attempt counting)
 * and tracks successful calls in the typed convenience arrays.
 */
class FakeDatabaseHostGateway extends DatabaseHostGateway
{
    /** @var list<array{method: string, args: list<mixed>}> */
    public array $calls = [];

    /** @var array<string, Throwable> */
    public array $throwOn = [];

    /** @var list<string> */
    public array $createdDatabases = [];

    /** @var list<string> */
    public array $droppedDatabases = [];

    /** @var list<array{username: string, remote: string, password: string, maxConnections: ?int}> */
    public array $createdUsers = [];

    /** @var list<array{username: string, remote: string}> */
    public array $droppedUsers = [];

    /** @var list<array{database: string, username: string, remote: string}> */
    public array $assignments = [];

    public int $flushCount = 0;

    private string $connection = DatabaseHostGateway::DEFAULT_CONNECTION_NAME;

    public function __construct() {}

    public function setConnection(string $connection): self
    {
        $this->connection = $connection;
        $this->calls[] = ['method' => 'setConnection', 'args' => [$connection]];

        return $this;
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    public function createDatabase(string $database): bool
    {
        $this->calls[] = ['method' => 'createDatabase', 'args' => [$database]];
        $this->maybeThrow('createDatabase');
        $this->createdDatabases[] = $database;

        return true;
    }

    public function createUser(string $username, string $remote, string $password, ?int $maxConnections): bool
    {
        $this->calls[] = ['method' => 'createUser', 'args' => [$username, $remote, $password, $maxConnections]];
        $this->maybeThrow('createUser');
        $this->createdUsers[] = [
            'username' => $username,
            'remote' => $remote,
            'password' => $password,
            'maxConnections' => $maxConnections,
        ];

        return true;
    }

    public function assignUserToDatabase(string $database, string $username, string $remote): bool
    {
        $this->calls[] = ['method' => 'assignUserToDatabase', 'args' => [$database, $username, $remote]];
        $this->maybeThrow('assignUserToDatabase');
        $this->assignments[] = [
            'database' => $database,
            'username' => $username,
            'remote' => $remote,
        ];

        return true;
    }

    public function flush(): bool
    {
        $this->calls[] = ['method' => 'flush', 'args' => []];
        $this->maybeThrow('flush');
        $this->flushCount++;

        return true;
    }

    public function dropDatabase(string $database): bool
    {
        $this->calls[] = ['method' => 'dropDatabase', 'args' => [$database]];
        $this->maybeThrow('dropDatabase');
        $this->droppedDatabases[] = $database;

        return true;
    }

    public function dropUser(string $username, string $remote): bool
    {
        $this->calls[] = ['method' => 'dropUser', 'args' => [$username, $remote]];
        $this->maybeThrow('dropUser');
        $this->droppedUsers[] = [
            'username' => $username,
            'remote' => $remote,
        ];

        return true;
    }

    /**
     * Count how many times a method was recorded in $calls.
     */
    public function count(string $method): int
    {
        $total = 0;
        foreach ($this->calls as $call) {
            if ($call['method'] === $method) {
                $total++;
            }
        }

        return $total;
    }

    public function assertCreated(string $database): void
    {
        Assert::assertContains($database, $this->createdDatabases, "Expected database [{$database}] to have been created.");
    }

    public function assertDroppedDatabase(string $database): void
    {
        Assert::assertContains($database, $this->droppedDatabases, "Expected database [{$database}] to have been dropped.");
    }

    public function assertDroppedUser(string $username, string $remote): void
    {
        Assert::assertContains(
            ['username' => $username, 'remote' => $remote],
            $this->droppedUsers,
            "Expected user [{$username}@{$remote}] to have been dropped."
        );
    }

    public function assertAssigned(string $database, string $username, string $remote): void
    {
        Assert::assertContains(
            ['database' => $database, 'username' => $username, 'remote' => $remote],
            $this->assignments,
            "Expected user [{$username}@{$remote}] to have been assigned to [{$database}]."
        );
    }

    public function assertFlushed(?int $times = null): void
    {
        if ($times === null) {
            Assert::assertGreaterThan(0, $this->flushCount, 'Expected flush to have been called at least once.');
        } else {
            Assert::assertSame($times, $this->flushCount, "Expected flush to have been called {$times} time(s).");
        }
    }

    /**
     * @throws Throwable
     */
    private function maybeThrow(string $method): void
    {
        if (isset($this->throwOn[$method])) {
            throw $this->throwOn[$method];
        }
    }
}
