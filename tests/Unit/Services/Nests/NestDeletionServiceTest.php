<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Services;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Services\Nests\NestDeletionService;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class NestDeletionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Nests\NestDeletionService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->repository = m::mock(NestRepositoryInterface::class);

        $this->service = new NestDeletionService($this->serverRepository, $this->repository);
    }

    /**
     * Test that a service is deleted when there are no servers attached to a service.
     */
    public function testServiceIsDeleted()
    {
        $this->serverRepository->shouldReceive('findCountWhere')->with([['nest_id', '=', 1]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with(1)->once()->andReturn(1);

        $this->assertEquals(1, $this->service->handle(1));
    }

    /**
     * Test that an exception is thrown when there are servers attached to a service.
     *
     * @dataProvider serverCountProvider
     *
     * @param int $count
     */
    public function testExceptionIsThrownIfServersAreAttached(int $count)
    {
        $this->serverRepository->shouldReceive('findCountWhere')->with([['nest_id', '=', 1]])->once()->andReturn($count);

        try {
            $this->service->handle(1);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(HasActiveServersException::class, $exception);
            $this->assertEquals(trans('exceptions.nest.delete_has_servers'), $exception->getMessage());
        }
    }

    /**
     * Provide assorted server counts to ensure that an exception is always thrown when more than 0 servers are found.
     *
     * @return array
     */
    public function serverCountProvider()
    {
        return [
            [1], [2], [5], [10],
        ];
    }
}
