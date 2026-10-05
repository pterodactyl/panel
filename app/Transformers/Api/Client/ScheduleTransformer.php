<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Client;

use League\Fractal\Resource\Collection;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;

#[ResponseField('next_run_at', example: '2026-06-30T04:00:00+00:00', nullable: true)]
class ScheduleTransformer extends BaseClientTransformer
{
    protected array $includeRelations = [
        'tasks' => ['relation' => 'tasks', 'transformer' => TaskTransformer::class],
    ];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['tasks'];

    /**
     * @var list<string>
     */
    protected array $defaultIncludes = ['tasks'];

    public function getResourceName(): string
    {
        return Schedule::RESOURCE_NAME;
    }

    /**
     * Returns a transformed schedule model such that a client can view the information.
     *
     * @return ApiPayload
     */
    public function transform(Schedule $model): array
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
            'last_run_at' => $model->last_run_at?->toAtomString(),
            'next_run_at' => $model->next_run_at?->toAtomString(),
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at->toAtomString(),
        ];
    }

    /**
     * Allows attaching the tasks specific to the schedule in the response.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeTasks(Schedule $model): Collection
    {
        return $this->collection(
            $model->tasks,
            $this->makeTransformer(TaskTransformer::class),
            Task::RESOURCE_NAME
        );
    }
}
