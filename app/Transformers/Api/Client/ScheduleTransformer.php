<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Task;
use Pterodactyl\Models\Schedule;
use Illuminate\Database\Eloquent\Model;

class ScheduleTransformer extends BaseClientTransformer
{
    /**
     * @var array
     */
    protected $availableIncludes = ['tasks'];

    /**
     * @var array
     */
    protected $defaultIncludes = ['tasks'];

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return Schedule::RESOURCE_NAME;
    }

    /**
     * Returns a transformed schedule model such that a client can view the information.
     *
     * @return array
     */
    public function transform(Schedule $model)
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'cron' => [
                'day_of_week' => $model->cron_day_of_week,
                'day_of_month' => $model->cron_day_of_month,
                'month' => $model->cron_month,
                'hour' => $model->cron_hour,
                'minute' => $model->cron_minute,
            ],
            'is_active' => $model->is_active,
            'is_processing' => $model->is_processing,
            'only_when_online' => $model->only_when_online,
            'last_run_at' => $model->last_run_at ? $model->last_run_at->toIso8601String() : null,
            'next_run_at' => $model->next_run_at ? $model->next_run_at->toIso8601String() : null,
            'created_at' => $model->created_at->toIso8601String(),
            'updated_at' => $model->updated_at->toIso8601String(),
        ];
    }

    /**
     * Allows attaching the tasks specific to the schedule in the response.
     *
     * @return \League\Fractal\Resource\Collection
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeTasks(Schedule $model)
    {
        return $this->collection(
            $model->tasks,
            $this->makeTransformer(TaskTransformer::class),
            Task::RESOURCE_NAME
        );
    }
}
