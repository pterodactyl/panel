<?php
/*
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

namespace Tests\Unit\Services\Locations;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Locations\LocationUpdateService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationUpdateServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Locations\LocationUpdateService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(LocationRepositoryInterface::class);

        $this->service = new LocationUpdateService($this->repository);
    }

    /**
     * Test location is updated.
     */
    public function testLocationIsUpdated()
    {
        $model = factory(Location::class)->make(['id' => 123]);
        $this->repository->shouldReceive('update')->with(123, ['test_data' => 'test_value'])->once()->andReturn($model);

        $response = $this->service->handle($model->id, ['test_data' => 'test_value']);
        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Location::class, $response);
    }

    /**
     * Test that a model can be passed in place of an ID.
     */
    public function testModelCanBePassedToFunction()
    {
        $model = factory(Location::class)->make(['id' => 123]);
        $this->repository->shouldReceive('update')->with(123, ['test_data' => 'test_value'])->once()->andReturn($model);

        $response = $this->service->handle($model, ['test_data' => 'test_value']);
        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Location::class, $response);
    }
}
