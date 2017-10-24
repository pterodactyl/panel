<?php

namespace Tests\Unit\Services\Databases\Hosts;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Services\Databases\Hosts\HostDeletionService;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class HostDeletionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface|\Mockery\Mock
     */
    private $databaseRepository;

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

        $this->databaseRepository = m::mock(DatabaseRepositoryInterface::class);
        $this->repository = m::mock(DatabaseHostRepositoryInterface::class);
    }

    /**
     * Test that a host can be deleted.
     */
    public function testHostIsDeleted()
    {
        $this->databaseRepository->shouldReceive('findCountWhere')->with([['database_host_id', '=', 1234]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with(1234)->once()->andReturn(1);

        $response = $this->getService()->handle(1234);
        $this->assertNotEmpty($response);
        $this->assertSame(1, $response);
    }

    /**
     * Test that an exception is thrown if a host with databases is deleted.
     *
     * @dataProvider databaseCountDataProvider
     */
    public function testExceptionIsThrownIfDeletingHostWithDatabases(int $count)
    {
        $this->databaseRepository->shouldReceive('findCountWhere')->with([['database_host_id', '=', 1234]])->once()->andReturn($count);

        try {
            $this->getService()->handle(1234);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(HasActiveServersException::class, $exception);
            $this->assertEquals(trans('exceptions.databases.delete_has_databases'), $exception->getMessage());
        }
    }

    /**
     * Data provider to ensure exceptions are thrown for any value > 0.
     *
     * @return array
     */
    public function databaseCountDataProvider(): array
    {
        return [[1], [2], [10]];
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Databases\Hosts\HostDeletionService
     */
    private function getService(): HostDeletionService
    {
        return new HostDeletionService($this->databaseRepository, $this->repository);
    }
}
