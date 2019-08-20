<?php

namespace App\Services\Schedules;

use Cron\CronExpression;
use Illuminate\Support\Arr;
use App\Models\Schedule;
use Illuminate\Database\ConnectionInterface;
use App\Contracts\Repository\TaskRepositoryInterface;
use App\Services\Schedules\Tasks\TaskCreationService;
use App\Contracts\Repository\ScheduleRepositoryInterface;

class ScheduleUpdateService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \App\Contracts\Repository\ScheduleRepositoryInterface
     */
    private $repository;

    /**
     * @var \App\Services\Schedules\Tasks\TaskCreationService
     */
    private $taskCreationService;

    /**
     * @var \App\Contracts\Repository\TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * ScheduleUpdateService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                      $connection
     * @param \App\Contracts\Repository\ScheduleRepositoryInterface $repository
     * @param \App\Services\Schedules\Tasks\TaskCreationService     $taskCreationService
     * @param \App\Contracts\Repository\TaskRepositoryInterface     $taskRepository
     */
    public function __construct(
        ConnectionInterface $connection,
        ScheduleRepositoryInterface $repository,
        TaskCreationService $taskCreationService,
        TaskRepositoryInterface $taskRepository
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->taskCreationService = $taskCreationService;
        $this->taskRepository = $taskRepository;
    }

    /**
     * Update an existing schedule by deleting all current tasks and re-inserting the
     * new values.
     *
     * @param \App\Models\Schedule $schedule
     * @param array                        $data
     * @param array                        $tasks
     * @return \App\Models\Schedule
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException
     */
    public function handle(Schedule $schedule, array $data, array $tasks): Schedule
    {
        $data = array_merge($data, [
            'next_run_at' => $this->getCronTimestamp($data),
        ]);

        $this->connection->beginTransaction();

        $schedule = $this->repository->update($schedule->id, $data);
        $this->taskRepository->deleteWhere([['schedule_id', '=', $schedule->id]]);

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
