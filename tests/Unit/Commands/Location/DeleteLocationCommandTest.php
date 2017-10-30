<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Commands\Location;

use Mockery as m;
use Pterodactyl\Models\Location;
use Tests\Unit\Commands\CommandTestCase;
use Pterodactyl\Services\Locations\LocationDeletionService;
use Pterodactyl\Console\Commands\Location\DeleteLocationCommand;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class DeleteLocationCommandTest extends CommandTestCase
{
    /**
     * @var \Pterodactyl\Console\Commands\Location\DeleteLocationCommand
     */
    protected $command;

    /**
     * @var \Pterodactyl\Services\Locations\LocationDeletionService|\Mockery\Mock
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->deletionService = m::mock(LocationDeletionService::class);
        $this->repository = m::mock(LocationRepositoryInterface::class);

        $this->command = new DeleteLocationCommand($this->deletionService, $this->repository);
        $this->command->setLaravel($this->app);
    }

    /**
     * Test that a location can be deleted.
     */
    public function testLocationIsDeleted()
    {
        $locations = collect([
            $location1 = factory(Location::class)->make(),
            $location2 = factory(Location::class)->make(),
        ]);

        $this->repository->shouldReceive('all')->withNoArgs()->once()->andReturn($locations);
        $this->deletionService->shouldReceive('handle')->with($location2->id)->once()->andReturnNull();

        $display = $this->runCommand($this->command, [], [$location2->short]);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.location.deleted'), $display);
    }

    /**
     * Test that a location is deleted if passed in as an option.
     */
    public function testLocationIsDeletedIfPassedInOption()
    {
        $locations = collect([
            $location1 = factory(Location::class)->make(),
            $location2 = factory(Location::class)->make(),
        ]);

        $this->repository->shouldReceive('all')->withNoArgs()->once()->andReturn($locations);
        $this->deletionService->shouldReceive('handle')->with($location2->id)->once()->andReturnNull();

        $display = $this->withoutInteraction()->runCommand($this->command, [
            '--short' => $location2->short,
        ]);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.location.deleted'), $display);
    }

    /**
     * Test that prompt shows back up if the user enters the wrong parameters.
     */
    public function testInteractiveEnvironmentAllowsReAttemptingSearch()
    {
        $locations = collect([
            $location1 = factory(Location::class)->make(),
            $location2 = factory(Location::class)->make(),
        ]);

        $this->repository->shouldReceive('all')->withNoArgs()->once()->andReturn($locations);
        $this->deletionService->shouldReceive('handle')->with($location2->id)->once()->andReturnNull();

        $display = $this->runCommand($this->command, [], ['123_not_exist', 'another_not_exist', $location2->short]);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.location.no_location_found'), $display);
        $this->assertContains(trans('command/messages.location.deleted'), $display);
    }

    /**
     * Test that no re-attempt is performed in a non-interactive environment.
     */
    public function testNonInteractiveEnvironmentThrowsErrorIfNoLocationIsFound()
    {
        $locations = collect([
            $location1 = factory(Location::class)->make(),
            $location2 = factory(Location::class)->make(),
        ]);

        $this->repository->shouldReceive('all')->withNoArgs()->once()->andReturn($locations);
        $this->deletionService->shouldNotReceive('handle');

        $display = $this->withoutInteraction()->runCommand($this->command, ['--short' => 'randomTestString']);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.location.no_location_found'), $display);
    }
}
