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
use Pterodactyl\Services\Locations\LocationDeletionService;
use Pterodactyl\Console\Commands\Location\DeleteLocationCommand;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class DeleteLocationCommandTest extends TestCase
{
    /**
     * @var \Pterodactyl\Console\Commands\Location\DeleteLocationCommand
     */
    protected $command;

    /**
     * @var \Pterodactyl\Services\Locations\LocationDeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
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

        $response = new CommandTester($this->command);
        $response->setInputs([$location2->short]);
        $response->execute([]);

        $display = $response->getDisplay();
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

        $response = new CommandTester($this->command);
        $response->execute([
            '--short' => $location2->short,
        ]);

        $display = $response->getDisplay();
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

        $response = new CommandTester($this->command);
        $response->setInputs(['123_not_exist', 'another_not_exist', $location2->short]);
        $response->execute([]);

        $display = $response->getDisplay();
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

        $response = new CommandTester($this->command);
        $response->execute(['--short' => 'randomTestString'], ['interactive' => false]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.location.no_location_found'), $display);
    }
}
