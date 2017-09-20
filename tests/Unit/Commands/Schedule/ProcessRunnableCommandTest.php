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
