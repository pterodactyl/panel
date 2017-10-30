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
use Pterodactyl\Models\DatabaseHost;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Repositories\Eloquent\DatabaseHostRepository;

class DatabaseHostRepositoryTest extends TestCase
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\DatabaseHostRepository
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->builder = m::mock(Builder::class);
        $this->repository = m::mock(DatabaseHostRepository::class)->makePartial();

        $this->repository->shouldReceive('getBuilder')->withNoArgs()->andReturn($this->builder);
    }

    /**
     * Test that we are returning the correct model.
     */
    public function testCorrectModelIsAssigned()
    {
        $this->assertEquals(DatabaseHost::class, $this->repository->model());
    }

    /**
     * Test query to reutrn all of the default view data.
     */
    public function testHostWithDefaultViewDataIsReturned()
    {
        $this->builder->shouldReceive('withCount')->with('databases')->once()->andReturnSelf()
            ->shouldReceive('with')->with('node')->once()->andReturnSelf()
            ->shouldReceive('get')->withNoArgs()->once()->andReturnNull();

        $this->assertNull($this->repository->getWithViewDetails());
    }

    /**
     * Test query to return host and servers.
     */
    public function testHostIsReturnedWithServers()
    {
        $model = factory(DatabaseHost::class)->make();

        $this->builder->shouldReceive('with')->with('databases.server')->once()->andReturnSelf()
            ->shouldReceive('find')->with(1, ['*'])->once()->andReturn($model);

        $this->assertEquals($model, $this->repository->getWithServers(1));
    }

    /**
     * Test exception is found if no host is found when querying for servers.
     *
     * @expectedException \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function testExceptionIsThrownIfNoRecordIsFoundWithServers()
    {
        $this->builder->shouldReceive('with')->with('databases.server')->once()->andReturnSelf()
            ->shouldReceive('find')->with(1, ['*'])->once()->andReturnNull();

        $this->repository->getWithServers(1);
    }
}
