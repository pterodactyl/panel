<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Services\Options;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Services\Eggs\EggDeletionService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\HasChildrenException;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class EggDeletionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Eggs\EggDeletionService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(EggRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);

        $this->service = new EggDeletionService($this->serverRepository, $this->repository);
    }

    /**
     * Test that Egg is deleted if no servers are found.
     */
    public function testEggIsDeletedIfNoServersAreFound()
    {
        $this->serverRepository->shouldReceive('findCountWhere')->with([['egg_id', '=', 1]])->once()->andReturn(0);
        $this->repository->shouldReceive('findCountWhere')->with([['config_from', '=', 1]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with(1)->once()->andReturn(1);

        $this->assertEquals(1, $this->service->handle(1));
    }

    /**
     * Test that Egg is not deleted if servers are found.
     */
    public function testExceptionIsThrownIfServersAreFound()
    {
        $this->serverRepository->shouldReceive('findCountWhere')->with([['egg_id', '=', 1]])->once()->andReturn(1);

        try {
            $this->service->handle(1);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(HasActiveServersException::class, $exception);
            $this->assertEquals(trans('exceptions.nest.egg.delete_has_servers'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if children Eggs exist.
     */
    public function testExceptionIsThrownIfChildrenArePresent()
    {
        $this->serverRepository->shouldReceive('findCountWhere')->with([['egg_id', '=', 1]])->once()->andReturn(0);
        $this->repository->shouldReceive('findCountWhere')->with([['config_from', '=', 1]])->once()->andReturn(1);

        try {
            $this->service->handle(1);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(HasChildrenException::class, $exception);
            $this->assertEquals(trans('exceptions.nest.egg.has_children'), $exception->getMessage());
        }
    }
}
