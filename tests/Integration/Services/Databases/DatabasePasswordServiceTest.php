<?php

namespace Pterodactyl\Tests\Integration\Services\Databases;

use Mockery\MockInterface;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Repositories\Eloquent\DatabaseRepository;
use Pterodactyl\Services\Databases\DatabasePasswordService;

class DatabasePasswordServiceTest extends IntegrationTestCase
{
    private MockInterface $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->mock(DatabaseRepository::class);
    }

    /**
     * Test that a database password is rotated correctly.
     */
    public function testDatabasePasswordCanBeRotated()
    {
        $server = $this->createServerModel();
        $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);

        $database = Database::factory()->create([
            'server_id' => $server->id,
            'database_host_id' => $host->id,
            'password' => encrypt('original'),
        ]);

        $other = Database::factory()->create([
            'server_id' => $server->id,
            'database_host_id' => $host->id,
            'password' => encrypt('unchanged'),
        ]);

        $password = null;

        $this->repository->expects('dropUser')->with($database->username, $database->remote);
        $this->repository->expects('createUser')->with(
            $database->username,
            $database->remote,
            \Mockery::on(function ($value) use (&$password) {
                $password = $value;

                return true;
            }),
            $database->max_connections
        );
        $this->repository->expects('assignUserToDatabase')->with($database->database, $database->username, $database->remote);
        $this->repository->expects('flush')->withNoArgs();

        $response = $this->getService()->handle($database);

        // The new password is returned, set on the host, and stored.
        $this->assertSame(24, strlen($response));
        $this->assertSame($response, $password);
        $this->assertSame($response, decrypt($database->refresh()->password));

        // Other databases are untouched.
        $this->assertSame('unchanged', decrypt($other->refresh()->password));
    }

    private function getService(): DatabasePasswordService
    {
        return $this->app->make(DatabasePasswordService::class);
    }
}
