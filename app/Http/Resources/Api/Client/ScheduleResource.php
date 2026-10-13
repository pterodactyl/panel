<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;

/**
 * @extends ClientResource<Schedule>
 */
#[ResponseField('next_run_at', example: '2026-06-30T04:00:00+00:00', nullable: true)]
class ScheduleResource extends ClientResource
{
    protected array $includeRelations = [
        'tasks' => ['relation' => 'tasks', 'resource' => TaskResource::class],
    ];

    protected array $availableIncludes = ['tasks'];

    protected array $defaultIncludes = ['tasks'];

    public function resourceName(): string
    {
        return Schedule::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'cron' => [
                'day_of_week' => $this->resource->cron_day_of_week,
                'day_of_month' => $this->resource->cron_day_of_month,
                'month' => $this->resource->cron_month,
                'hour' => $this->resource->cron_hour,
                'minute' => $this->resource->cron_minute,
            ],
            'is_active' => $this->resource->is_active,
            'is_processing' => $this->resource->is_processing,
            'only_when_online' => $this->resource->only_when_online,
            'last_run_at' => $this->resource->last_run_at?->toAtomString(),
            'next_run_at' => $this->resource->next_run_at?->toAtomString(),
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'tasks' => $this->includeTasks(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeTasks(): ApiResourceCollection
    {
        return $this->items($this->resource->tasks, TaskResource::class, Task::RESOURCE_NAME);
    }
}
