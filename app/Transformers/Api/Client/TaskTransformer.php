<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Task;
use Pterodactyl\Transformers\Api\Transformer;

class TaskTransformer extends Transformer
{
    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return Task::RESOURCE_NAME;
    }

    /**
     * Transforms a schedule's task into a client viewable format.
     */
    public function transform(Task $model): array
    {
        return [
            'id' => $model->id,
            'sequence_id' => $model->sequence_id,
            'action' => $model->action,
            'payload' => $model->payload,
            'time_offset' => $model->time_offset,
            'is_queued' => $model->is_queued,
            'continue_on_failure' => $model->continue_on_failure,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }
}
