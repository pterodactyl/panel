<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Locations;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Locations\LocationCreationService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationCreationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Locations\LocationCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(LocationRepositoryInterface::class);

        $this->service = new LocationCreationService($this->repository);
    }

    /**
     * Test that a location is created.
     */
    public function testLocationIsCreated()
    {
        $location = factory(Location::class)->make();

        $this->repository->shouldReceive('create')->with(['test_data' => 'test_value'])->once()->andReturn($location);

        $response = $this->service->handle(['test_data' => 'test_value']);
        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Location::class, $response);
        $this->assertEquals($location, $response);
    }
}
