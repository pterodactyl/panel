<?php

namespace Tests\Unit\Jobs\Schedule;

use Mockery as m;
use Carbon\Carbon;
use Tests\TestCase;
use Cake\Chronos\Chronos;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\User;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Illuminate\Support\Facades\Bus;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Repositories\Eloquent\TaskRepository;
use Pterodactyl\Services\Backups\InitiateBackupService;
use Pterodactyl\Repositories\Eloquent\ScheduleRepository;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class RunTaskJobTest extends TestCase
{
    /**
     * @var \Mockery\MockInterface
     */
    private $commandRepository;

    /**
     * @var \Mockery\MockInterface
     */
    private $powerRepository;

    /**
     * @var \Mockery\MockInterface
     */
    private $initiateBackupService;

    /**
     * @var \Mockery\MockInterface
     */
    private $taskRepository;

    /**
     * @var \Mockery\MockInterface
     */
    private $scheduleRepository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Carbon::setTestNow(Carbon::now());

        $this->commandRepository = m::mock(DaemonCommandRepository::class);
        $this->powerRepository = m::mock(DaemonPowerRepository::class);
        $this->taskRepository = m::mock(TaskRepository::class);
        $this->initiateBackupService = m::mock(InitiateBackupService::class);
        $this->scheduleRepository = m::mock(ScheduleRepository::class);

        $this->app->instance(TaskRepositoryInterface::class, $this->taskRepository);
        $this->app->instance(ScheduleRepositoryInterface::class, $this->scheduleRepository);
    }

    /**
     * Test power option passed to job.
     */
    public function testPowerAction()
    {
        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = factory(Schedule::class)->make(['is_active' => true]);

        /** @var \Pterodactyl\Models\Task $task */
        $task = factory(Task::class)->make(['action' => 'power', 'sequence_id' => 1]);

        /* @var \Pterodactyl\Models\Server $server */
        $task->setRelation('server', $server = factory(Server::class)->make());
        $task->setRelation('schedule', $schedule);
        $server->setRelation('user', factory(User::class)->make());

        $this->taskRepository->expects('getTaskForJobProcess')->with($task->id)->andReturn($task);
        $this->powerRepository->expects('setServer')->with($task->server)->andReturnSelf()
            ->getMock()->expects('send')->with($task->payload)->andReturn(new Response);

        $this->taskRepository->shouldReceive('update')->with($task->id, ['is_queued' => false])->once()->andReturnNull();
        $this->taskRepository->shouldReceive('getNextTask')->with($schedule->id, $task->sequence_id)->once()->andReturnNull();

        $this->scheduleRepository->shouldReceive('withoutFreshModel->update')->with($schedule->id, [
            'is_processing' => false,
            'last_run_at' => Chronos::now()->toDateTimeString(),
        ])->once()->andReturnNull();

        $this->getJobInstance($task->id, $schedule->id);

        Bus::assertNotDispatched(RunTaskJob::class);
    }

    /**
     * Test command action passed to job.
     */
    public function testCommandAction()
    {
        $schedule = factory(Schedule::class)->make();
        $task = factory(Task::class)->make(['action' => 'command', 'sequence_id' => 1]);
        $task->setRelation('server', $server = factory(Server::class)->make());
        $task->setRelation('schedule', $schedule);
        $server->setRelation('user', factory(User::class)->make());

        $this->taskRepository->expects('getTaskForJobProcess')->with($task->id)->andReturn($task);
        $this->commandRepository->expects('setServer')->with($task->server)->andReturnSelf()
            ->getMock()->expects('send')->with($task->payload)->andReturn(new Response);

        $this->taskRepository->expects('update')->with($task->id, ['is_queued' => false])->andReturnNull();
        $this->taskRepository->expects('getNextTask')->with($schedule->id, $task->sequence_id)->andReturnNull();

        $this->scheduleRepository->shouldReceive('withoutFreshModel->update')->with($schedule->id, [
            'is_processing' => false,
            'last_run_at' => Chronos::now()->toDateTimeString(),
        ])->once()->andReturnNull();

        $this->getJobInstance($task->id, $schedule->id);

        Bus::assertNotDispatched(RunTaskJob::class);
    }

    /**
     * Test that the next task in the list is queued if the current one is not the last.
     */
    public function testNextTaskQueuedIfExists()
    {
        $schedule = factory(Schedule::class)->make();
        $task = factory(Task::class)->make(['action' => 'command', 'sequence_id' => 1]);
        $task->setRelation('server', $server = factory(Server::class)->make());
        $task->setRelation('schedule', $schedule);
        $server->setRelation('user', factory(User::class)->make());

        $this->taskRepository->expects('getTaskForJobProcess')->with($task->id)->andReturn($task);
        $this->commandRepository->expects('setServer')->with($task->server)->andReturnSelf()
            ->getMock()->expects('send')->with($task->payload)->andReturn(new Response);

        $this->taskRepository->shouldReceive('update')->with($task->id, ['is_queued' => false])->once()->andReturnNull();

        $nextTask = factory(Task::class)->make();
        $this->taskRepository->expects('getNextTask')->with($schedule->id, $task->sequence_id)->andReturn($nextTask);
        $this->taskRepository->expects('update')->with($nextTask->id, [
            'is_queued' => true,
        ])->andReturnNull();

        $this->getJobInstance($task->id, $schedule->id);

        Bus::assertDispatched(RunTaskJob::class, function ($job) use ($nextTask, $schedule) {
            $this->assertEquals($nextTask->id, $job->task, 'Assert correct task ID is passed to job.');
            $this->assertEquals($schedule->id, $job->schedule, 'Assert correct schedule ID is passed to job.');
            $this->assertEquals($nextTask->time_offset, $job->delay, 'Assert correct job delay time is set.');

            return true;
        });
    }

    /**
     * Test that an exception is thrown if an invalid task action is supplied.
     */
    public function testInvalidActionPassedToJob()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot run a task that points to a non-existent action.');

        $schedule = factory(Schedule::class)->make();
        $task = factory(Task::class)->make(['action' => 'invalid', 'sequence_id' => 1]);
        $task->setRelation('server', $server = factory(Server::class)->make());
        $task->setRelation('schedule', $schedule);
        $server->setRelation('user', factory(User::class)->make());

        $this->taskRepository->expects('getTaskForJobProcess')->with($task->id)->andReturn($task);

        $this->getJobInstance($task->id, 1234);
    }

    /**
     * Test that a schedule marked as disabled does not get processed.
     */
    public function testScheduleMarkedAsDisabledDoesNotProcess()
    {
        $schedule = factory(Schedule::class)->make(['is_active' => false]);
        $task = factory(Task::class)->make(['action' => 'invalid', 'sequence_id' => 1]);
        $task->setRelation('server', $server = factory(Server::class)->make());
        $task->setRelation('schedule', $schedule);
        $server->setRelation('user', factory(User::class)->make());

        $this->taskRepository->shouldReceive('getTaskForJobProcess')->with($task->id)->once()->andReturn($task);

        $this->scheduleRepository->shouldReceive('withoutFreshModel->update')->with($schedule->id, [
            'is_processing' => false,
            'last_run_at' => Chronos::now()->toDateTimeString(),
        ])->once()->andReturn(1);

        $this->taskRepository->shouldReceive('update')->with($task->id, ['is_queued' => false])->once()->andReturn(1);

        $this->getJobInstance($task->id, $schedule->id);
        $this->assertTrue(true);
    }

    /**
     * Run the job using the mocks provided.
     *
     * @param int $task
     * @param int $schedule
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    private function getJobInstance($task, $schedule)
    {
        return (new RunTaskJob($task, $schedule))->handle(
            $this->commandRepository,
            $this->initiateBackupService,
            $this->powerRepository,
            $this->taskRepository
        );
    }
}
