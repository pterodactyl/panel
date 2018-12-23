<?php

namespace Tests\Unit\Services\Databases;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Services\Databases\DeployServerDatabaseService;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class DeployServerDatabaseServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface|\Mockery\Mock
     */
    private $databaseHostRepository;

    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService|\Mockery\Mock
     */
    private $managementService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->databaseHostRepository = m::mock(DatabaseHostRepositoryInterface::class);
        $this->managementService = m::mock(DatabaseManagementService::class);
        $this->repository = m::mock(DatabaseRepositoryInterface::class);

        // Set configs for testing instances.
        config()->set('pterodactyl.client_features.databases.enabled', true);
        config()->set('pterodactyl.client_features.databases.allow_random', true);
    }

    /**
     * Test handling of non-random hosts when a host is found.
     *
     * @dataProvider databaseLimitDataProvider
     */
    public function testNonRandomFoundHost($limit, $count)
    {
        config()->set('pterodactyl.client_features.databases.allow_random', false);

        $server = factory(Server::class)->make(['database_limit' => $limit]);
        $model = factory(Database::class)->make();

        $this->repository->shouldReceive('findCountWhere')
            ->once()
            ->with([['server_id', '=', $server->id]])
            ->andReturn($count);

        $this->databaseHostRepository->shouldReceive('setColumns->findWhere')
            ->once()
            ->with([['node_id', '=', $server->node_id]])
            ->andReturn(collect([$model]));

        $this->managementService->shouldReceive('create')
            ->once()
            ->with($server->id, [
                'database_host_id' => $model->id,
                'database' => 'testdb',
                'remote' => null,
            ])
            ->andReturn($model);

        $response = $this->getService()->handle($server, ['database' => 'testdb']);

        $this->assertInstanceOf(Database::class, $response);
        $this->assertSame($model, $response);
    }

    /**
     * Test that an exception is thrown if in non-random mode and no host is found.
     *
     * @expectedException \Pterodactyl\Exceptions\Service\Database\NoSuitableDatabaseHostException
     */
    public function testNonRandomNoHost()
    {
        config()->set('pterodactyl.client_features.databases.allow_random', false);

        $server = factory(Server::class)->make(['database_limit' => 1]);

        $this->repository->shouldReceive('findCountWhere')
            ->once()
            ->with([['server_id', '=', $server->id]])
            ->andReturn(0);

        $this->databaseHostRepository->shouldReceive('setColumns->findWhere')
            ->once()
            ->with([['node_id', '=', $server->node_id]])
            ->andReturn(collect());

        $this->getService()->handle($server, []);
    }

    /**
     * Test handling of random host selection.
     */
    public function testRandomFoundHost()
    {
        $server = factory(Server::class)->make(['database_limit' => 1]);
        $model = factory(Database::class)->make();

        $this->repository->shouldReceive('findCountWhere')
            ->once()
            ->with([['server_id', '=', $server->id]])
            ->andReturn(0);

        $this->databaseHostRepository->shouldReceive('setColumns->findWhere')
            ->once()
            ->with([['node_id', '=', $server->node_id]])
            ->andReturn(collect());

        $this->databaseHostRepository->shouldReceive('setColumns->all')
            ->once()
            ->andReturn(collect([$model]));

        $this->managementService->shouldReceive('create')
            ->once()
            ->with($server->id, [
                'database_host_id' => $model->id,
                'database' => 'testdb',
                'remote' => null,
            ])
            ->andReturn($model);

        $response = $this->getService()->handle($server, ['database' => 'testdb']);

        $this->assertInstanceOf(Database::class, $response);
        $this->assertSame($model, $response);
    }

    /**
     * Test that an exception is thrown when no host is found and random is allowed.
     *
     * @expectedException \Pterodactyl\Exceptions\Service\Database\NoSuitableDatabaseHostException
     */
    public function testRandomNoHost()
    {
        $server = factory(Server::class)->make(['database_limit' => 1]);

        $this->repository->shouldReceive('findCountWhere')
            ->once()
            ->with([['server_id', '=', $server->id]])
            ->andReturn(0);

        $this->databaseHostRepository->shouldReceive('setColumns->findWhere')
            ->once()
            ->with([['node_id', '=', $server->node_id]])
            ->andReturn(collect());

        $this->databaseHostRepository->shouldReceive('setColumns->all')
            ->once()
            ->andReturn(collect());

        $this->getService()->handle($server, []);
    }

    /**
     * Test that a server over the database limit throws an exception.
     *
     * @dataProvider databaseExceedingLimitDataProvider
     * @expectedException \Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException
     */
    public function testServerOverDatabaseLimit($limit, $count)
    {
        $server = factory(Server::class)->make(['database_limit' => $limit]);

        $this->repository->shouldReceive('findCountWhere')
            ->once()
            ->with([['server_id', '=', $server->id]])
            ->andReturn($count);

        $this->getService()->handle($server, []);
    }

    /**
     * Test that an exception is thrown if the feature is not enabled.
     *
     * @expectedException \Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     */
    public function testFeatureNotEnabled()
    {
        config()->set('pterodactyl.client_features.databases.enabled', false);

        $this->getService()->handle(factory(Server::class)->make(), []);
    }

    /**
     * Provide limits and current database counts for testing.
     *
     * @return array
     */
    public function databaseLimitDataProvider(): array
    {
        return [
            [null, 10],
            [1, 0],
        ];
    }

    /**
     * Provide data for servers over their database limit.
     *
     * @return array
     */
    public function databaseExceedingLimitDataProvider(): array
    {
        return [
            [2, 2],
            [2, 3],
        ];
    }

    /**
     * Return an instance of the service with mocked dependencies for testing.
     *
     * @return \Pterodactyl\Services\Databases\DeployServerDatabaseService
     */
    private function getService(): DeployServerDatabaseService
    {
        return new DeployServerDatabaseService($this->repository, $this->databaseHostRepository, $this->managementService);
    }
}
