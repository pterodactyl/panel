<?php

namespace Tests\Unit\Services\Schedules;

use Mockery as m;
use Tests\TestCase;
use Cron\CronExpression;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\Schedule;
use Illuminate\Contracts\Bus\Dispatcher;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Services\Schedules\ProcessScheduleService;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessScheduleServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher|\Mockery\Mock
     */
    private $dispatcher;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface|\Mockery\Mock
     */
    private $scheduleRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface|\Mockery\Mock
     */
    private $taskRepository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->dispatcher = m::mock(Dispatcher::class);
        $this->scheduleRepository = m::mock(ScheduleRepositoryInterface::class);
        $this->taskRepository = m::mock(TaskRepositoryInterface::class);
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

        $this->scheduleRepository->shouldReceive('loadTasks')->with($model)->once()->andReturn($model);

        $formatted = sprintf('%s %s %s * %s', $model->cron_minute, $model->cron_hour, $model->cron_day_of_month, $model->cron_day_of_week);
        $this->scheduleRepository->shouldReceive('update')->with($model->id, [
            'is_processing' => true,
            'next_run_at' => CronExpression::factory($formatted)->getNextRunDate(),
        ]);

        $this->taskRepository->shouldReceive('update')->with($task->id, ['is_queued' => true])->once();

        $this->dispatcher->shouldReceive('dispatch')->with(m::on(function ($class) use ($model, $task) {
            $this->assertInstanceOf(RunTaskJob::class, $class);
            $this->assertSame($task->time_offset, $class->delay);
            $this->assertSame($task->id, $class->task);
            $this->assertSame($model->id, $class->schedule);

            return true;
        }))->once();

        $this->getService()->handle($model);
        $this->assertTrue(true);
    }

    /**
     * Return an instance of the service for testing purposes.
     *
     * @return \Pterodactyl\Services\Schedules\ProcessScheduleService
     */
    private function getService(): ProcessScheduleService
    {
        return new ProcessScheduleService($this->dispatcher, $this->scheduleRepository, $this->taskRepository);
    }
}
