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
use Pterodactyl\Services\Locations\LocationCreationService;
use Pterodactyl\Console\Commands\Location\MakeLocationCommand;

class MakeLocationCommandTest extends CommandTestCase
{
    /**
     * @var \Pterodactyl\Console\Commands\Location\MakeLocationCommand
     */
    protected $command;

    /**
     * @var \Pterodactyl\Services\Locations\LocationCreationService|\Mockery\Mock
     */
    protected $creationService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->creationService = m::mock(LocationCreationService::class);

        $this->command = new MakeLocationCommand($this->creationService);
        $this->command->setLaravel($this->app);
    }

    /**
     * Test that a location can be created when no options are passed.
     */
    public function testLocationIsCreatedWithNoOptionsPassed()
    {
        $location = factory(Location::class)->make();

        $this->creationService->shouldReceive('handle')->with([
            'short' => $location->short,
            'long' => $location->long,
        ])->once()->andReturn($location);

        $display = $this->runCommand($this->command, [], [$location->short, $location->long]);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.location.created', [
            'name' => $location->short,
            'id' => $location->id,
        ]), $display);
    }

    /**
     * Test that a location is created when options are passed.
     */
    public function testLocationIsCreatedWhenOptionsArePassed()
    {
        $location = factory(Location::class)->make();

        $this->creationService->shouldReceive('handle')->with([
            'short' => $location->short,
            'long' => $location->long,
        ])->once()->andReturn($location);

        $display = $this->withoutInteraction()->runCommand($this->command, [
            '--short' => $location->short,
            '--long' => $location->long,
        ]);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.location.created', [
            'name' => $location->short,
            'id' => $location->id,
        ]), $display);
    }
}
