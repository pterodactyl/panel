<?php

namespace Tests\Unit\Services\Databases\Hosts;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\DatabaseHost;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Services\Databases\Hosts\HostCreationService;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class HostCreationServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    private $connection;

    /**
     * @var \Illuminate\Database\DatabaseManager|\Mockery\Mock
     */
    private $databaseManager;

    /**
     * @var \Pterodactyl\Extensions\DynamicDatabaseConnection|\Mockery\Mock
     */
    private $dynamic;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter|\Mockery\Mock
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->databaseManager = m::mock(DatabaseManager::class);
        $this->dynamic = m::mock(DynamicDatabaseConnection::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->repository = m::mock(DatabaseHostRepositoryInterface::class);
    }

    /**
     * Test that a database host can be created.
     */
    public function testDatabaseHostIsCreated()
    {
        $model = factory(DatabaseHost::class)->make();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldReceive('encrypt')->with('test123')->once()->andReturn('enc123');
        $this->repository->shouldReceive('create')->with(m::subset([
            'password' => 'enc123',
            'username' => $model->username,
            'node_id' => $model->node_id,
        ]))->once()->andReturn($model);

        $this->dynamic->shouldReceive('set')->with('dynamic', $model)->once()->andReturnNull();
        $this->databaseManager->shouldReceive('connection')->with('dynamic')->once()->andReturnSelf();
        $this->databaseManager->shouldReceive('select')->with('SELECT 1 FROM dual')->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->getService()->handle([
            'password' => 'test123',
            'username' => $model->username,
            'node_id' => $model->node_id,
        ]);

        $this->assertNotEmpty($response);
        $this->assertSame($model, $response);
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Databases\Hosts\HostCreationService
     */
    private function getService(): HostCreationService
    {
        return new HostCreationService(
            $this->connection,
            $this->databaseManager,
            $this->repository,
            $this->dynamic,
            $this->encrypter
        );
    }
}
