<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
