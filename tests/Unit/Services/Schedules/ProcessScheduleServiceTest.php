<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Schedules;

use Mockery as m;
use Tests\TestCase;
use Cron\CronExpression;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Services\Schedules\Tasks\RunTaskService;
use Pterodactyl\Services\Schedules\ProcessScheduleService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessScheduleServiceTest extends TestCase
{
    /**
     * @var \Cron\CronExpression|\Mockery\Mock
     */
    protected $cron;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Schedules\Tasks\RunTaskService|\Mockery\Mock
     */
    protected $runnerService;

    /**
     * @var \Pterodactyl\Services\Schedules\ProcessScheduleService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->cron = m::mock('overload:' . CronExpression::class);
        $this->repository = m::mock(ScheduleRepositoryInterface::class);
        $this->runnerService = m::mock(RunTaskService::class);

        $this->service = new ProcessScheduleService($this->runnerService, $this->repository);
    }

    /**
     * Test that a schedule can be updated and first task set to run.
     */
    public function testScheduleIsUpdatedAndRun()
    {
        $model = factory(Schedule::class)->make();
        $model->setRelation('tasks', collect([$task = factory(Task::class)->make([
            'sequence_id' => 1,
        ])]));

        $formatted = sprintf('%s %s %s * %s *', $model->cron_minute, $model->cron_hour, $model->cron_day_of_month, $model->cron_day_of_week);

        $this->cron->shouldReceive('factory')->with($formatted)->once()->andReturnSelf()
            ->shouldReceive('getNextRunDate')->withNoArgs()->once()->andReturn('00:00:00');

        $this->repository->shouldReceive('update')->with($model->id, [
            'is_processing' => true,
            'next_run_at' => '00:00:00',
        ]);

        $this->runnerService->shouldReceive('handle')->with($task)->once()->andReturnNull();

        $this->service->handle($model);
        $this->assertTrue(true);
    }

    /**
     * Test that passing a schedule model without a tasks relation is handled.
     */
    public function testScheduleModelWithoutTasksIsHandled()
    {
        $nonRelationModel = factory(Schedule::class)->make();
        $model = clone $nonRelationModel;
        $model->setRelation('tasks', collect([$task = factory(Task::class)->make([
            'sequence_id' => 1,
        ])]));

        $formatted = sprintf('%s %s %s * %s *', $model->cron_minute, $model->cron_hour, $model->cron_day_of_month, $model->cron_day_of_week);

        $this->repository->shouldReceive('getScheduleWithTasks')->with($nonRelationModel->id)->once()->andReturn($model);
        $this->cron->shouldReceive('factory')->with($formatted)->once()->andReturnSelf()
            ->shouldReceive('getNextRunDate')->withNoArgs()->once()->andReturn('00:00:00');

        $this->repository->shouldReceive('update')->with($model->id, [
            'is_processing' => true,
            'next_run_at' => '00:00:00',
        ]);

        $this->runnerService->shouldReceive('handle')->with($task)->once()->andReturnNull();

        $this->service->handle($nonRelationModel);
        $this->assertTrue(true);
    }

    /**
     * Test that a task ID can be passed in place of the task model.
     */
    public function testPassingScheduleIdInPlaceOfModelIsHandled()
    {
        $model = factory(Schedule::class)->make();
        $model->setRelation('tasks', collect([$task = factory(Task::class)->make([
            'sequence_id' => 1,
        ])]));

        $formatted = sprintf('%s %s %s * %s *', $model->cron_minute, $model->cron_hour, $model->cron_day_of_month, $model->cron_day_of_week);

        $this->repository->shouldReceive('getScheduleWithTasks')->with($model->id)->once()->andReturn($model);
        $this->cron->shouldReceive('factory')->with($formatted)->once()->andReturnSelf()
            ->shouldReceive('getNextRunDate')->withNoArgs()->once()->andReturn('00:00:00');

        $this->repository->shouldReceive('update')->with($model->id, [
            'is_processing' => true,
            'next_run_at' => '00:00:00',
        ]);

        $this->runnerService->shouldReceive('handle')->with($task)->once()->andReturnNull();

        $this->service->handle($model->id);
        $this->assertTrue(true);
    }
}
