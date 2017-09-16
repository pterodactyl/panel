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

namespace Tests\Unit\Commands\Location;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Location;
use Symfony\Component\Console\Tester\CommandTester;
use Pterodactyl\Services\Locations\LocationCreationService;
use Pterodactyl\Console\Commands\Location\MakeLocationCommand;

class MakeLocationCommandTest extends TestCase
{
    /**
     * @var \Pterodactyl\Console\Commands\Location\MakeLocationCommand
     */
    protected $command;

    /**
     * @var \Pterodactyl\Services\Locations\LocationCreationService
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

        $response = new CommandTester($this->command);
        $response->setInputs([$location->short, $location->long]);
        $response->execute([]);

        $display = $response->getDisplay();
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

        $response = new CommandTester($this->command);
        $response->execute([
            '--short' => $location->short,
            '--long' => $location->long,
        ]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.location.created', [
            'name' => $location->short,
            'id' => $location->id,
        ]), $display);
    }
}
