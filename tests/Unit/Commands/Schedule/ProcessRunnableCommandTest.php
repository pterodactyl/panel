<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Commands\Schedule;

use Mockery as m;
use Carbon\Carbon;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\Schedule;
use Tests\Unit\Commands\CommandTestCase;
use Pterodactyl\Services\Schedules\ProcessScheduleService;
use Pterodactyl\Console\Commands\Schedule\ProcessRunnableCommand;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessRunnableCommandTest extends CommandTestCase
{
    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var \Pterodactyl\Console\Commands\Schedule\ProcessRunnableCommand
     */
    protected $command;

    /**
     * @var \Pterodactyl\Services\Schedules\ProcessScheduleService
     */
    protected $processScheduleService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->carbon = m::mock(Carbon::class);
        $this->processScheduleService = m::mock(ProcessScheduleService::class);
        $this->repository = m::mock(ScheduleRepositoryInterface::class);

        $this->command = new ProcessRunnableCommand($this->carbon, $this->processScheduleService, $this->repository);
    }

    /**
     * Test that a schedule can be queued up correctly.
     */
    public function testScheduleIsQueued()
    {
        $schedule = factory(Schedule::class)->make();
        $schedule->tasks = collect([factory(Task::class)->make()]);

        $this->carbon->shouldReceive('now')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('toAtomString')->withNoArgs()->once()->andReturn('00:00:00');
        $this->repository->shouldReceive('getSchedulesToProcess')->with('00:00:00')->once()->andReturn(collect([$schedule]));
        $this->processScheduleService->shouldReceive('handle')->with($schedule)->once()->andReturnNull();

        $display = $this->runCommand($this->command);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.schedule.output_line', [
            'schedule' => $schedule->name,
            'hash' => $schedule->hashid,
        ]), $display);
    }

    /**
     * If tasks is an empty collection, don't process it.
     */
    public function testScheduleWithNoTasksIsNotProcessed()
    {
        $schedule = factory(Schedule::class)->make();
        $schedule->tasks = collect([]);

        $this->carbon->shouldReceive('now')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('toAtomString')->withNoArgs()->once()->andReturn('00:00:00');
        $this->repository->shouldReceive('getSchedulesToProcess')->with('00:00:00')->once()->andReturn(collect([$schedule]));

        $display = $this->runCommand($this->command);

        $this->assertNotEmpty($display);
        $this->assertNotContains(trans('command/messages.schedule.output_line', [
            'schedule' => $schedule->name,
            'hash' => $schedule->hashid,
        ]), $display);
    }

    /**
     * If tasks isn't an instance of a collection, don't process it.
     */
    public function testScheduleWithTasksObjectThatIsNotInstanceOfCollectionIsNotProcessed()
    {
        $schedule = factory(Schedule::class)->make(['tasks' => null]);

        $this->carbon->shouldReceive('now')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('toAtomString')->withNoArgs()->once()->andReturn('00:00:00');
        $this->repository->shouldReceive('getSchedulesToProcess')->with('00:00:00')->once()->andReturn(collect([$schedule]));

        $display = $this->runCommand($this->command);

        $this->assertNotEmpty($display);
        $this->assertNotContains(trans('command/messages.schedule.output_line', [
            'schedule' => $schedule->name,
            'hash' => $schedule->hashid,
        ]), $display);
    }
}
