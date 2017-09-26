<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Repositories\Eloquent;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Repositories\Eloquent\LocationRepository;

class LocationRepositoryTest extends TestCase
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\LocationRepository
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->builder = m::mock(Builder::class);
        $this->repository = m::mock(LocationRepository::class)->makePartial();

        $this->repository->shouldReceive('getBuilder')->withNoArgs()->andReturn($this->builder);
    }

    /**
     * Test that we are returning the correct model.
     */
    public function testCorrectModelIsAssigned()
    {
        $this->assertEquals(Location::class, $this->repository->model());
    }

    /**
     * Test that all locations with associated node and server counts are returned.
     */
    public function testAllLocationsWithDetailsAreReturned()
    {
        $this->builder->shouldReceive('withCount')->with('nodes', 'servers')->once()->andReturnSelf()
            ->shouldReceive('get')->with(['*'])->once()->andReturnNull();

        $this->assertNull($this->repository->getAllWithDetails());
    }

    /**
     * Test that all locations with associated node are returned.
     */
    public function testAllLocationsWithNodes()
    {
        $this->builder->shouldReceive('with')->with('nodes')->once()->andReturnSelf()
            ->shouldReceive('get')->with(['*'])->once()->andReturnNull();

        $this->assertNull($this->repository->getAllWithNodes());
    }

    /**
     * Test that a single location with associated node is returned.
     */
    public function testLocationWithNodeIsReturned()
    {
        $model = factory(Location::class)->make();

        $this->builder->shouldReceive('with')->with('nodes.servers')->once()->andReturnSelf()
            ->shouldReceive('find')->with(1, ['*'])->once()->andReturn($model);

        $response = $this->repository->getWithNodes(1);
        $this->assertInstanceOf(Location::class, $response);
        $this->assertEquals($model, $response);
    }

    /**
     * Test that an exception is thrown when getting location with nodes if no location is found.
     *
     * @expectedException  \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function testExceptionIsThrownIfNoLocationIsFoundWithNodes()
    {
        $this->builder->shouldReceive('with')->with('nodes.servers')->once()->andReturnSelf()
            ->shouldReceive('find')->with(1, ['*'])->once()->andReturnNull();

        $this->repository->getWithNodes(1);
    }
}
