<?php
/**
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
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Services\Schedules\Tasks\TaskCreationService;
use Pterodactyl\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException;

class TaskCreationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Schedules\Tasks\TaskCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(TaskRepositoryInterface::class);

        $this->service = new TaskCreationService($this->repository);
    }

    /**
     * Test that a task is created and a model is returned for the task.
     *
     * @dataProvider validIntervalProvider
     */
    public function testTaskIsCreatedAndModelReturned($interval, $value, $final)
    {
        $schedule = factory(Schedule::class)->make();
        $task = factory(Task::class)->make();

        $this->repository->shouldReceive('create')->with([
            'schedule_id' => $schedule->id,
            'sequence_id' => 1,
            'action' => $task->action,
            'payload' => $task->payload,
            'time_offset' => $final,
        ], false)->once()->andReturn($task);

        $response = $this->service->handle($schedule, [
            'time_interval' => $interval,
            'time_value' => $value,
            'sequence_id' => 1,
            'action' => $task->action,
            'payload' => $task->payload,
        ]);

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Task::class, $response);
        $this->assertEquals($task, $response);
    }

    /**
     * Test that no new model is returned when a task is created.
     */
    public function testTaskIsCreatedAndModelIsNotReturned()
    {
        $schedule = factory(Schedule::class)->make();

        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('create')->with([
                'schedule_id' => $schedule->id,
                'sequence_id' => 1,
                'action' => 'test',
                'payload' => 'testpayload',
                'time_offset' => 300,
            ], false)->once()->andReturn(true);

        $response = $this->service->handle($schedule, [
            'time_interval' => 'm',
            'time_value' => 5,
            'sequence_id' => 1,
            'action' => 'test',
            'payload' => 'testpayload',
        ], false);

        $this->assertNotEmpty($response);
        $this->assertNotInstanceOf(Task::class, $response);
        $this->assertTrue($response);
    }

    /**
     * Test that an ID can be passed in place of the schedule model itself.
     */
    public function testIdCanBePassedInPlaceOfScheduleModel()
    {
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('create')->with([
                'schedule_id' => 1234,
                'sequence_id' => 1,
                'action' => 'test',
                'payload' => 'testpayload',
                'time_offset' => 300,
            ], false)->once()->andReturn(true);

        $response = $this->service->handle(1234, [
            'time_interval' => 'm',
            'time_value' => 5,
            'sequence_id' => 1,
            'action' => 'test',
            'payload' => 'testpayload',
        ], false);

        $this->assertNotEmpty($response);
        $this->assertNotInstanceOf(Task::class, $response);
        $this->assertTrue($response);
    }

    /**
     * Test exception is thrown if the interval is greater than 15 minutes.
     *
     * @dataProvider invalidIntervalProvider
     */
    public function testExceptionIsThrownIfIntervalIsMoreThan15Minutes($interval, $value)
    {
        try {
            $this->service->handle(1234, [
                'time_interval' => $interval,
                'time_value' => $value,
            ]);
        } catch (DisplayException $exception) {
            $this->assertInstanceOf(TaskIntervalTooLongException::class, $exception);
            $this->assertEquals(trans('exceptions.tasks.chain_interval_too_long'), $exception->getMessage());
        }
    }

    /**
     * Test that exceptions are thrown if the Schedule module or ID is invalid.
     *
     * @dataProvider invalidScheduleArgumentProvider
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionIsThrownIfInvalidArgumentIsPassed($argument)
    {
        $this->service->handle($argument, []);
    }

    /**
     * Provides valid time intervals to be used in tests.
     *
     * @return array
     */
    public function validIntervalProvider()
    {
        return [
            ['s', 30, 30],
            ['s', 60, 60],
            ['s', 90, 90],
            ['m', 1, 60],
            ['m', 5, 300],
        ];
    }

    /**
     * Return invalid time formats.
     *
     * @return array
     */
    public function invalidIntervalProvider()
    {
        return [
            ['m', 15.1],
            ['m', 16],
            ['s', 901],
        ];
    }

    /**
     * Return an array of invalid schedule data to test against.
     *
     * @return array
     */
    public function invalidScheduleArgumentProvider()
    {
        return [
            [123.456],
            ['string'],
            ['abc123'],
            ['123_test'],
            [new Server()],
            [Schedule::class],
        ];
    }
}
