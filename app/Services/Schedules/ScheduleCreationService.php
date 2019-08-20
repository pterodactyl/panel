<?php

namespace App\Services\Schedules;

use Cron\CronExpression;
use Illuminate\Support\Arr;
use App\Models\Server;
use Illuminate\Database\ConnectionInterface;
use App\Services\Schedules\Tasks\TaskCreationService;
use App\Contracts\Repository\ScheduleRepositoryInterface;

class ScheduleCreationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \App\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Services\Schedules\Tasks\TaskCreationService
     */
    protected $taskCreationService;

    /**
     * ScheduleCreationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                      $connection
     * @param \App\Contracts\Repository\ScheduleRepositoryInterface $repository
     * @param \App\Services\Schedules\Tasks\TaskCreationService     $taskCreationService
     */
    public function __construct(
        ConnectionInterface $connection,
        ScheduleRepositoryInterface $repository,
        TaskCreationService $taskCreationService
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->taskCreationService = $taskCreationService;
    }

    /**
     * Create a new schedule for a specific server.
     *
     * @param \App\Models\Server $server
     * @param array                      $data
     * @param array                      $tasks
     * @return \App\Models\Schedule
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException
     */
    public function handle(Server $server, array $data, array $tasks = [])
    {
        $data = array_merge($data, [
            'server_id' => $server->id,
            'next_run_at' => $this->getCronTimestamp($data),
        ]);

        $this->connection->beginTransaction();
        $schedule = $this->repository->create($data);

        foreach ($tasks as $index => $task) {
            $this->taskCreationService->handle($schedule, [
                'time_interval' => Arr::get($task, 'time_interval'),
                'time_value' => Arr::get($task, 'time_value'),
                'sequence_id' => $index + 1,
                'action' => Arr::get($task, 'action'),
                'payload' => Arr::get($task, 'payload'),
            ], false);
        }

        $this->connection->commit();

        return $schedule;
    }

    /**
     * Return a DateTime object after parsing the cron data provided.
     *
     * @param array $data
     * @return \DateTime
     */
    private function getCronTimestamp(array $data)
    {
        $formattedCron = sprintf('%s %s %s * %s',
            Arr::get($data, 'cron_minute', '*'),
            Arr::get($data, 'cron_hour', '*'),
            Arr::get($data, 'cron_day_of_month', '*'),
            Arr::get($data, 'cron_day_of_week', '*')
        );

        return CronExpression::factory($formattedCron)->getNextRunDate();
    }
}
