<?php

namespace Tests\Unit\Services\Schedules;

use Mockery as m;
use Tests\TestCase;
use Cron\CronExpression;
use Pterodactyl\Models\Schedule;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Schedules\ScheduleUpdateService;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Services\Schedules\Tasks\TaskCreationService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ScheduleUpdateServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Schedules\Tasks\TaskCreationService|\Mockery\Mock
     */
    private $taskCreationService;

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

        $this->connection = m::mock(ConnectionInterface::class);
        $this->repository = m::mock(ScheduleRepositoryInterface::class);
        $this->taskCreationService = m::mock(TaskCreationService::class);
        $this->taskRepository = m::mock(TaskRepositoryInterface::class);
    }

    /**
     * Test that a schedule can be updated.
     */
    public function testUpdate()
    {
        $schedule = factory(Schedule::class)->make();
        $tasks = [['action' => 'test-action']];
        $data = [
            'cron_minute' => 1,
            'cron_hour' => 2,
            'cron_day_of_month' => 3,
            'cron_day_of_week' => 4,
            'next_run_at' => '_INVALID_VALUE',
        ];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs();
        $this->repository->shouldReceive('update')->once()->with($schedule->id, array_merge($data, [
            'next_run_at' => CronExpression::factory('1 2 3 * 4 *')->getNextRunDate(),
        ]))->andReturn($schedule);

        $this->taskRepository->shouldReceive('deleteWhere')->once()->with([['schedule_id', '=', $schedule->id]]);
        $this->taskCreationService->shouldReceive('handle')->once()->with($schedule, m::subset([
            'sequence_id' => 1,
            'action' => 'test-action',
        ]), false);

        $this->connection->shouldReceive('commit')->once()->withNoArgs();

        $response = $this->getService()->handle($schedule, $data, $tasks);
        $this->assertInstanceOf(Schedule::class, $response);
        $this->assertSame($schedule, $response);
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Schedules\ScheduleUpdateService
     */
    private function getService(): ScheduleUpdateService
    {
        return new ScheduleUpdateService(
            $this->connection,
            $this->repository,
            $this->taskCreationService,
            $this->taskRepository
        );
    }
}
