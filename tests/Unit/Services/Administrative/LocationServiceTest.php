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

namespace Tests\Unit\Services;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Services\Administrative\LocationService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Administrative\LocationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(LocationRepositoryInterface::class);

        $this->service = new LocationService($this->repository);
    }

    /**
     * Test that creating a location returns the correct information.
     */
    public function test_create_location()
    {
        $data = ['short' => 'shortCode', 'long' => 'longCode'];

        $this->repository->shouldReceive('create')->with($data)->once()->andReturn((object) $data);

        $response = $this->service->create($data);

        $this->assertNotNull($response);
        $this->assertObjectHasAttribute('short', $response);
        $this->assertObjectHasAttribute('long', $response);
        $this->assertEquals('shortCode', $response->short);
        $this->assertEquals('longCode', $response->long);
    }

    /**
     * Test that updating a location updates it correctly.
     */
    public function test_update_location()
    {
        $data = ['short' => 'newShort'];

        $this->repository->shouldReceive('update')->with(1, $data)->once()->andReturn((object) $data);

        $response = $this->service->update(1, $data);

        $this->assertNotNull($response);
        $this->assertObjectHasAttribute('short', $response);
        $this->assertEquals('newShort', $response->short);
    }

    /**
     * Test that a location deletion returns valid data.
     */
    public function test_delete_location()
    {
        $this->repository->shouldReceive('deleteIfNoNodes')->with(1)->once()->andReturn(true);

        $response = $this->service->delete(1);

        $this->assertTrue($response);
    }
}
