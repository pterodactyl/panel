<?php

namespace Tests\Unit\Services\Schedules;

use Mockery as m;
use Carbon\Carbon;
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
        Carbon::setTestNow(Carbon::now());

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

        $this->repository->shouldReceive('loadTasks')->with($model)->once()->andReturn($model);

        $formatted = sprintf('%s %s %s * %s *', $model->cron_minute, $model->cron_hour, $model->cron_day_of_month, $model->cron_day_of_week);
        $this->repository->shouldReceive('update')->with($model->id, [
            'is_processing' => true,
            'next_run_at' => CronExpression::factory($formatted)->getNextRunDate(),
        ]);

        $this->runnerService->shouldReceive('handle')->with($task)->once()->andReturnNull();

        $this->service->handle($model);
        $this->assertTrue(true);
    }

    public function testScheduleRunTimeCanBeOverridden()
    {
        $model = factory(Schedule::class)->make();
        $model->setRelation('tasks', collect([$task = factory(Task::class)->make([
            'sequence_id' => 1,
        ])]));

        $this->repository->shouldReceive('loadTasks')->with($model)->once()->andReturn($model);

        $this->repository->shouldReceive('update')->with($model->id, [
            'is_processing' => true,
            'next_run_at' => Carbon::now()->addSeconds(15)->toDateTimeString(),
        ]);

        $this->runnerService->shouldReceive('handle')->with($task)->once()->andReturnNull();

        $this->service->setRunTimeOverride(Carbon::now()->addSeconds(15))->handle($model);
        $this->assertTrue(true);
    }
}
