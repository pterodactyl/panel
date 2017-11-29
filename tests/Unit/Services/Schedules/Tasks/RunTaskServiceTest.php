<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Schedules\Tasks;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Task;
use Illuminate\Support\Facades\Bus;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Services\Schedules\Tasks\RunTaskService;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;

class RunTaskServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher|\Mockery\Mock
     */
    protected $dispatcher;

    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Schedules\Tasks\RunTaskService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        Bus::fake();
        $this->repository = m::mock(TaskRepositoryInterface::class);

        $this->service = new RunTaskService($this->repository);
    }

    /**
     * Test that a job is dispatched.
     */
    public function testTaskIsDispatched()
    {
        $task = factory(Task::class)->make();

        $this->repository->shouldReceive('update')->with($task->id, ['is_queued' => true])->once()->andReturnNull();

        $this->service->handle($task);

        Bus::assertDispatched(RunTaskJob::class, function ($job) use ($task) {
            $this->assertEquals($task->id, $job->task, 'Assert job task matches parent task model.');
            $this->assertEquals($task->schedule_id, $job->schedule, 'Assert job is linked to correct schedule.');
            $this->assertEquals($task->time_offset, $job->delay, 'Assert job delay is set correctly to match task.');

            return true;
        });
    }

    /**
     * Test that passing an ID in place of a model works.
     */
    public function testIdCanBePassedInPlaceOfModel()
    {
        $task = factory(Task::class)->make();

        $this->repository->shouldReceive('find')->with($task->id)->once()->andReturn($task);
        $this->repository->shouldReceive('update')->with($task->id, ['is_queued' => true])->once()->andReturnNull();

        $this->service->handle($task->id);

        Bus::assertDispatched(RunTaskJob::class, function ($job) use ($task) {
            $this->assertEquals($task->id, $job->task, 'Assert job task matches parent task model.');
            $this->assertEquals($task->schedule_id, $job->schedule, 'Assert job is linked to correct schedule.');
            $this->assertEquals($task->time_offset, $job->delay, 'Assert job delay is set correctly to match task.');

            return true;
        });
    }
}
