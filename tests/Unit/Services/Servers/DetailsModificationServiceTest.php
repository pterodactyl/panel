<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use App\Models\Server;
use Illuminate\Database\ConnectionInterface;
use App\Repositories\Eloquent\ServerRepository;
use App\Services\Servers\DetailsModificationService;
use App\Services\DaemonKeys\DaemonKeyCreationService;
use App\Services\DaemonKeys\DaemonKeyDeletionService;

class DetailsModificationServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    private $connection;

    /**
     * @var \App\Services\DaemonKeys\DaemonKeyCreationService|\Mockery\Mock
     */
    private $keyCreationService;

    /**
     * @var \App\Services\DaemonKeys\DaemonKeyDeletionService|\Mockery\Mock
     */
    private $keyDeletionService;

    /**
     * @var \App\Repositories\Eloquent\ServerRepository|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->keyCreationService = m::mock(DaemonKeyCreationService::class);
        $this->keyDeletionService = m::mock(DaemonKeyDeletionService::class);
        $this->repository = m::mock(ServerRepository::class);
    }

    /**
     * Test basic updating of core variables when a model is provided.
     */
    public function testDetailsAreEdited()
    {
        $server = factory(Server::class)->make(['owner_id' => 1]);

        $data = ['owner_id' => 1, 'name' => 'New Name', 'description' => 'New Description'];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->repository->shouldReceive('setFreshModel')->once()->with(false)->andReturnSelf();
        $this->repository->shouldReceive('update')->once()->with($server->id, [
                'external_id' => null,
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
            ], true, true)->andReturn(true);

        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $response = $this->getService()->handle($server, $data);
        $this->assertTrue($response);
    }

    /**
     * Test that a model is returned if requested.
     */
    public function testModelIsReturned()
    {
        $server = factory(Server::class)->make(['owner_id' => 1]);

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->repository->shouldReceive('setFreshModel')->once()->with(true)->andReturnSelf();
        $this->repository->shouldReceive('update')->once()->andReturn($server);

        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $response = $this->getService()->returnUpdatedModel()->handle($server, ['owner_id' => 1]);
        $this->assertInstanceOf(Server::class, $response);
    }

    /**
     * Test that the daemon secret is reset if the owner id changes.
     */
    public function testEditShouldResetDaemonSecretIfOwnerIdIsChanged()
    {
        $server = factory(Server::class)->make([
            'owner_id' => 1,
        ]);

        $data = ['owner_id' => 2, 'name' => 'New Name', 'description' => 'New Description', 'external_id' => 'abcd1234'];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->repository->shouldReceive('setFreshModel')->once()->with(false)->andReturnSelf();
        $this->repository->shouldReceive('update')->once()->with($server->id, [
                'external_id' => 'abcd1234',
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
            ], true, true)->andReturn(true);

        $this->keyDeletionService->shouldReceive('handle')->once()->with($server, $server->owner_id)->andReturnNull();
        $this->keyCreationService->shouldReceive('handle')->once()->with($server->id, $data['owner_id'])->andReturnNull();
        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $response = $this->getService()->handle($server, $data);
        $this->assertTrue($response);
    }

    /**
     * Return an instance of the service with mocked dependencies for testing.
     *
     * @return \App\Services\Servers\DetailsModificationService
     */
    private function getService(): DetailsModificationService
    {
        return new DetailsModificationService(
            $this->connection,
            $this->keyCreationService,
            $this->keyDeletionService,
            $this->repository
        );
    }
}
