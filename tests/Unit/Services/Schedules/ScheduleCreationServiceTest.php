<?php

namespace Tests\Unit\Services\Schedules;

use Mockery as m;
use Tests\TestCase;
use Cron\CronExpression;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Schedules\ScheduleCreationService;
use Pterodactyl\Services\Schedules\Tasks\TaskCreationService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ScheduleCreationServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Cron\CronExpression
     */
    protected $cron;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Schedules\ScheduleCreationService
     */
    protected $service;

    /**
     * @var \Pterodactyl\Services\Schedules\Tasks\TaskCreationService
     */
    protected $taskCreationService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->repository = m::mock(ScheduleRepositoryInterface::class);
        $this->taskCreationService = m::mock(TaskCreationService::class);

        $this->service = new ScheduleCreationService($this->connection, $this->repository, $this->taskCreationService);
    }

    /**
     * Test that a schedule with no tasks can be created.
     */
    public function testScheduleWithNoTasksIsCreated()
    {
        $schedule = factory(Schedule::class)->make();
        $server = factory(Server::class)->make();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('create')->with([
            'server_id' => $server->id,
            'next_run_at' => CronExpression::factory('* * * * *')->getNextRunDate(),
            'test_key' => 'value',
        ])->once()->andReturn($schedule);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($server, ['test_key' => 'value', 'server_id' => '123abc']);
        $this->assertInstanceOf(Schedule::class, $response);
        $this->assertEquals($schedule, $response);
    }

    /**
     * Test that a schedule with at least one task can be created.
     */
    public function testScheduleWithTasksIsCreated()
    {
        $schedule = factory(Schedule::class)->make();
        $server = factory(Server::class)->make();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('create')->with([
            'server_id' => $server->id,
            'next_run_at' => CronExpression::factory('* * * * *')->getNextRunDate(),
            'test_key' => 'value',
        ])->once()->andReturn($schedule);

        $this->taskCreationService->shouldReceive('handle')->with($schedule, [
            'time_interval' => 'm',
            'time_value' => 10,
            'sequence_id' => 1,
            'action' => 'test',
            'payload' => 'testpayload',
        ], false)->once()->andReturnNull();

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($server, ['test_key' => 'value'], [
            ['time_interval' => 'm', 'time_value' => 10, 'action' => 'test', 'payload' => 'testpayload'],
        ]);
        $this->assertInstanceOf(Schedule::class, $response);
        $this->assertEquals($schedule, $response);
    }
}
