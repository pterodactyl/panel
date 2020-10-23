<?php

namespace Pterodactyl\Tests\Integration\Services\Schedules;

use Mockery;
use Pterodactyl\Models\Task;
use InvalidArgumentException;
use Pterodactyl\Models\Schedule;
use Illuminate\Support\Facades\Bus;
use Illuminate\Contracts\Bus\Dispatcher;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Services\Schedules\ProcessScheduleService;

class ProcessScheduleServiceTest extends IntegrationTestCase
{
    /**
     * Test that a schedule with no tasks registered returns an error.
     */
    public function testScheduleWithNoTasksReturnsException()
    {
        $server = $this->createServerModel();
        $schedule = factory(Schedule::class)->create(['server_id' => $server->id]);

        $this->expectException(DisplayException::class);
        $this->expectExceptionMessage('Cannot process schedule for task execution: no tasks are registered.');

        $this->getService()->handle($schedule);
    }

    /**
     * Test that an error during the schedule update is not persisted to the database.
     */
    public function testErrorDuringScheduleDataUpdateDoesNotPersistChanges()
    {
        $server = $this->createServerModel();

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = factory(Schedule::class)->create([
            'server_id' => $server->id,
            'cron_minute' => 'hodor', // this will break the getNextRunDate() function.
        ]);

        /** @var \Pterodactyl\Models\Task $task */
        $task = factory(Task::class)->create(['schedule_id' => $schedule->id, 'sequence_id' => 1]);

        $this->expectException(InvalidArgumentException::class);

        $this->getService()->handle($schedule);

        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id, 'is_processing' => true]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id, 'is_queued' => true]);
    }

    /**
     * Test that a job is dispatched as expected using the initial delay.
     *
     * @param bool $now
     * @dataProvider dispatchNowDataProvider
     */
    public function testJobCanBeDispatchedWithExpectedInitialDelay($now)
    {
        $this->swap(Dispatcher::class, $dispatcher = Mockery::mock(Dispatcher::class));

        $server = $this->createServerModel();

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = factory(Schedule::class)->create(['server_id' => $server->id]);

        /** @var \Pterodactyl\Models\Task $task */
        $task = factory(Task::class)->create(['schedule_id' => $schedule->id, 'time_offset' => 10, 'sequence_id' => 1]);

        $dispatcher->expects($now ? 'dispatchNow' : 'dispatch')->with(Mockery::on(function (RunTaskJob $job) use ($task) {
            return $task->id === $job->task->id && $job->delay === 10;
        }));

        $this->getService()->handle($schedule, $now);

        $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'is_processing' => true]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'is_queued' => true]);
    }

    /**
     * Test that even if a schedule's task sequence gets messed up the first task based on
     * the ascending order of tasks is used.
     *
     * @see https://github.com/pterodactyl/panel/issues/2534
     */
    public function testFirstSequenceTaskIsFound()
    {
        $this->swap(Dispatcher::class, $dispatcher = Mockery::mock(Dispatcher::class));

        $server = $this->createServerModel();
        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = factory(Schedule::class)->create(['server_id' => $server->id]);

        /** @var \Pterodactyl\Models\Task $task */
        $task2 = factory(Task::class)->create(['schedule_id' => $schedule->id, 'sequence_id' => 4]);
        $task = factory(Task::class)->create(['schedule_id' => $schedule->id, 'sequence_id' => 2]);
        $task3 = factory(Task::class)->create(['schedule_id' => $schedule->id, 'sequence_id' => 3]);

        $dispatcher->expects('dispatch')->with(Mockery::on(function (RunTaskJob $job) use ($task) {
            return $task->id === $job->task->id;
        }));

        $this->getService()->handle($schedule);

        $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'is_processing' => true]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'is_queued' => true]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'is_queued' => false]);
        $this->assertDatabaseHas('tasks', ['id' => $task3->id, 'is_queued' => false]);
    }

    /**
     * @return array
     */
    public function dispatchNowDataProvider(): array
    {
        return [[true], [false]];
    }

    /**
     * @return \Pterodactyl\Services\Schedules\ProcessScheduleService
     */
    private function getService()
    {
        return $this->app->make(ProcessScheduleService::class);
    }
}
