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
