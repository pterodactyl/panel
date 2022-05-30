<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Models\ActivityLog;

class ActivityLogTransformer extends BaseClientTransformer
{
    protected array $availableIncludes = ['actor'];

    public function getResourceName(): string
    {
        return ActivityLog::RESOURCE_NAME;
    }

    public function transform(ActivityLog $model): array
    {
        return [
            'batch' => $model->batch,
            'event' => $model->event,
            'ip' => $model->ip,
            'description' => $model->description,
            'properties' => $model->properties,
            'timestamp' => $model->timestamp->toIso8601String(),
        ];
    }

    public function includeActor(ActivityLog $model)
    {
        if (!$model->actor instanceof User) {
            return $this->null();
        }

        return $this->item($model->actor, $this->makeTransformer(UserTransformer::class), User::RESOURCE_NAME);
    }
}
