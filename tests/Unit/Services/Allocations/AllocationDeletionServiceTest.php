<?php

namespace Tests\Unit\Services\Allocations;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException;

class AllocationDeletionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = m::mock(AllocationRepositoryInterface::class);
    }

    /**
     * Test that an allocation is deleted.
     */
    public function testAllocationIsDeleted()
    {
        $model = factory(Allocation::class)->make(['id' => 123]);

        $this->repository->expects('delete')->with($model->id)->andReturns(1);

        $response = $this->getService()->handle($model);
        $this->assertEquals(1, $response);
    }

    /**
     * Test that an exception gets thrown if an allocation is currently assigned to a server.
     */
    public function testExceptionThrownIfAssignedToServer()
    {
        $this->expectException(ServerUsingAllocationException::class);

        $model = factory(Allocation::class)->make(['server_id' => 123]);

        $this->getService()->handle($model);
    }

    /**
     * Return an instance of the service with mocked injections.
     *
     * @return \Pterodactyl\Services\Allocations\AllocationDeletionService
     */
    private function getService(): AllocationDeletionService
    {
        return new AllocationDeletionService($this->repository);
    }
}
