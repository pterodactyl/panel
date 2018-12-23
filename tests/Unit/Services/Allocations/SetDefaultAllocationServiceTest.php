<?php

namespace Tests\Unit\Services\Allocations;

use Mockery as m;
use Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Tests\Traits\MocksRequestException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Services\Allocations\SetDefaultAllocationService;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonRepositoryInterface;

class SetDefaultAllocationServiceTest extends TestCase
{
    use MocksRequestException;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
     */
    private $daemonRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $serverRepository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->daemonRepository = m::mock(DaemonRepositoryInterface::class);
        $this->repository = m::mock(AllocationRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
    }

    /**
     * Test that an allocation can be updated.
     *
     * @dataProvider useModelDataProvider
     */
    public function testAllocationIsUpdated(bool $useModel)
    {
        $allocations = factory(Allocation::class)->times(2)->make();
        $model = factory(Server::class)->make();
        if (! $useModel) {
            $this->serverRepository->shouldReceive('find')->with(1234)->once()->andReturn($model);
        }

        $this->repository->shouldReceive('findWhere')->with([['server_id', '=', $model->id]])->once()->andReturn($allocations);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->serverRepository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf();
        $this->serverRepository->shouldReceive('update')->with($model->id, [
            'allocation_id' => $allocations->first()->id,
        ])->once()->andReturn(new Response);

        $this->daemonRepository->shouldReceive('setServer')->with($model)->once()->andReturnSelf();
        $this->daemonRepository->shouldReceive('update')->with([
            'build' => [
                'default' => [
                    'ip' => $allocations->first()->ip,
                    'port' => $allocations->first()->port,
                ],
                'ports|overwrite' => $allocations->groupBy('ip')->map(function ($item) {
                    return $item->pluck('port');
                })->toArray(),
            ],
        ])->once()->andReturn(new Response);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->getService()->handle($useModel ? $model : 1234, $allocations->first()->id);
        $this->assertNotEmpty($response);
        $this->assertSame($allocations->first(), $response);
    }

    /**
     * Test that an allocation that doesn't belong to a server throws an exception.
     *
     * @expectedException \Pterodactyl\Exceptions\Service\Allocation\AllocationDoesNotBelongToServerException
     */
    public function testAllocationNotBelongingToServerThrowsException()
    {
        $model = factory(Server::class)->make();
        $this->repository->shouldReceive('findWhere')->with([['server_id', '=', $model->id]])->once()->andReturn(collect());

        $this->getService()->handle($model, 1234);
    }

    /**
     * Test that an exception thrown by guzzle is handled properly.
     */
    public function testExceptionThrownByGuzzleIsHandled()
    {
        $this->configureExceptionMock();

        $allocation = factory(Allocation::class)->make();
        $model = factory(Server::class)->make();

        $this->repository->shouldReceive('findWhere')->with([['server_id', '=', $model->id]])->once()->andReturn(collect([$allocation]));
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->serverRepository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf();
        $this->serverRepository->shouldReceive('update')->with($model->id, [
            'allocation_id' => $allocation->id,
        ])->once()->andReturn(new Response);

        $this->daemonRepository->shouldReceive('setServer->update')->once()->andThrow($this->getExceptionMock());
        $this->connection->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();

        try {
            $this->getService()->handle($model, $allocation->id);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(DaemonConnectionException::class, $exception);
            $this->assertInstanceOf(RequestException::class, $exception->getPrevious());
        }
    }

    /**
     * Data provider to determine if a model should be passed or an int.
     *
     * @return array
     */
    public function useModelDataProvider(): array
    {
        return [[false], [true]];
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Allocations\SetDefaultAllocationService
     */
    private function getService(): SetDefaultAllocationService
    {
        return new SetDefaultAllocationService($this->repository, $this->connection, $this->daemonRepository, $this->serverRepository);
    }
}
