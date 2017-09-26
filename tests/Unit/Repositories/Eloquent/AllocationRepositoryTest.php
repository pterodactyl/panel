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
use Pterodactyl\Models\Allocation;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;

class AllocationRepositoryTest extends TestCase
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\AllocationRepository
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->builder = m::mock(Builder::class);
        $this->repository = m::mock(AllocationRepository::class)->makePartial();

        $this->repository->shouldReceive('getBuilder')->withNoArgs()->andReturn($this->builder);
    }

    /**
     * Test that we are returning the correct model.
     */
    public function testCorrectModelIsAssigned()
    {
        $this->assertEquals(Allocation::class, $this->repository->model());
    }

    /**
     * Test that allocations can be assigned to a server correctly.
     */
    public function testAllocationsAreAssignedToAServer()
    {
        $this->builder->shouldReceive('whereIn')->with('id', [1, 2])->once()->andReturnSelf()
            ->shouldReceive('update')->with(['server_id' => 10])->once()->andReturn(true);

        $this->assertTrue($this->repository->assignAllocationsToServer(10, [1, 2]));
    }

    /**
     * Test that allocations with a node relationship are returned.
     */
    public function testAllocationsForANodeAreReturned()
    {
        $this->builder->shouldReceive('where')->with('node_id', 1)->once()->andReturnSelf()
            ->shouldReceive('get')->once()->andReturn(factory(Allocation::class)->make());

        $this->assertInstanceOf(Allocation::class, $this->repository->getAllocationsForNode(1));
    }
}
