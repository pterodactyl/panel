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
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Locations\LocationDeletionService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Exceptions\Service\Location\HasActiveNodesException;

class LocationDeletionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Locations\LocationDeletionService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->nodeRepository = m::mock(NodeRepositoryInterface::class);
        $this->repository = m::mock(LocationRepositoryInterface::class);

        $this->service = new LocationDeletionService($this->repository, $this->nodeRepository);
    }

    /**
     * Test that a location is deleted.
     */
    public function testLocationIsDeleted()
    {
        $this->nodeRepository->shouldReceive('findCountWhere')->with([['location_id', '=', 123]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with(123)->once()->andReturn(1);

        $response = $this->service->handle(123);
        $this->assertEquals(1, $response);
    }

    /**
     * Test that an exception is thrown if nodes are attached to a location.
     */
    public function testExceptionIsThrownIfNodesAreAttached()
    {
        $this->nodeRepository->shouldReceive('findCountWhere')->with([['location_id', '=', 123]])->once()->andReturn(1);

        try {
            $this->service->handle(123);
        } catch (DisplayException $exception) {
            $this->assertInstanceOf(HasActiveNodesException::class, $exception);
            $this->assertEquals(trans('exceptions.locations.has_nodes'), $exception->getMessage());
        }
    }
}
