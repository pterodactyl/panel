<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Task;

class TaskTransformer extends BaseClientTransformer
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
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at->toAtomString(),
        ];
    }
}
